<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby;

use Bnomei\Seo\Core\Renderer\HeadTagRenderer;
use Bnomei\Seo\Core\Renderer\RobotsTxtRenderer;
use Bnomei\Seo\Core\Renderer\SitemapXmlRenderer;
use Bnomei\Seo\Core\Service\SeoResolver;
use Bnomei\Seo\Core\Value\SeoContentSnapshot;
use Bnomei\Seo\Core\Value\SeoContext;
use Bnomei\Seo\Core\Value\SeoDocumentType;
use Bnomei\Seo\Core\Value\SeoResolvedDocument;
use Bnomei\Seo\Core\Value\SeoResolvedMeta;
use Bnomei\Seo\Core\Value\SeoRobotsRuleGroup;
use Bnomei\Seo\Core\Value\SeoRobotsRules;
use Bnomei\Seo\Core\Value\SeoRuleSet;
use Bnomei\Seo\Core\Value\SeoSitemapEntry;
use Bnomei\Seo\Core\Value\SeoVisibility;
use DateTimeImmutable;
use Kirby\Cms\App;
use Kirby\Cms\ModelWithContent;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Throwable;

final class SeoRuntimeBridge
{
    private const META_IMAGE_THUMB_PRESET = 'seo';

    /**
     * @var array<string, SeoResolvedDocument>
     */
    private static array $documentCache = [];

    public function __construct(
        private readonly App $kirby,
        private readonly ?SeoOptionReader $options = null,
        private readonly SeoResolver $resolver = new SeoResolver(),
        private readonly HeadTagRenderer $headTagRenderer = new HeadTagRenderer(),
        private readonly RobotsTxtRenderer $robotsTxtRenderer = new RobotsTxtRenderer(),
        private readonly SitemapXmlRenderer $sitemapXmlRenderer = new SitemapXmlRenderer(),
    ) {}

    public static function for(App $kirby): self
    {
        return new self(kirby: $kirby, options: new SeoOptionReader($kirby));
    }

    public static function resetDocumentCache(): void
    {
        self::$documentCache = [];
    }

    public function resolvePage(Page $page, ?string $languageCode = null): SeoResolvedDocument
    {
        $languageCode ??= $this->languageCode();
        $cacheKey = $this->cacheKey('page:' . $page->id() . ':' . $languageCode);

        if (isset(self::$documentCache[$cacheKey]) === true) {
            return self::$documentCache[$cacheKey];
        }

        $context = $this->pageContext($page, $languageCode);
        $snapshot = $this->pageSnapshot($page, $context);
        $rules = $this->rulesFor($page, $context, $snapshot);
        $document = $this->resolver->resolve($context, $snapshot, $rules);
        $document = $this->applyPostResolveFilters($page, $context, $document);

        return self::$documentCache[$cacheKey] = $document;
    }

    public function resolveSite(Site $site, ?string $languageCode = null): SeoResolvedDocument
    {
        $languageCode ??= $this->languageCode();
        $cacheKey = $this->cacheKey('site:' . $languageCode);

        if (isset(self::$documentCache[$cacheKey]) === true) {
            return self::$documentCache[$cacheKey];
        }

        $context = $this->siteContext($site, $languageCode);
        $snapshot = $this->siteSnapshot($site, $context);
        $rules = $this->rulesFor($site, $context, $snapshot);
        $document = $this->resolver->resolve($context, $snapshot, $rules);
        $document = $this->applyPostResolveFilters($site, $context, $document);

        return self::$documentCache[$cacheKey] = $document;
    }

    public function headTagsFor(Page|Site $model, ?string $languageCode = null): string
    {
        $document = $model instanceof Page
            ? $this->resolvePage($model, $languageCode)
            : $this->resolveSite($model, $languageCode);

        return $this->headTagRenderer->render($document);
    }

    public function robotsRules(?string $languageCode = null): SeoRobotsRules
    {
        $languageCode ??= $this->languageCode();
        $context = $this->siteContext($this->kirby->site(), $languageCode);

        $groups = $this->options()->robotsTxtGroups([$context, $this->kirby->site()], $this->defaultRobotsGroups());
        $groups = $this->options()->robotsTxtFilter([$context, $groups, $this->kirby->site()], $groups);

        return new SeoRobotsRules(groups: $this->ruleGroups($groups), sitemapUrls: [$this->robotsSitemapUrl($context)]);
    }

    public function robotsTxt(?string $languageCode = null): string
    {
        return $this->robotsTxtRenderer->render($this->robotsRules($languageCode));
    }

    public function sitemapEntries(?string $languageCode = null): array
    {
        $languageCode ??= $this->languageCode();
        $generator = $this->options()->sitemapGenerator([$this->kirby->site(), $languageCode]);

        if (is_array($generator) === true) {
            return array_values(array_filter(
                $generator,
                static fn(mixed $entry): bool => $entry instanceof SeoSitemapEntry,
            ));
        }

        $entries = [];

        foreach ($this->kirby->site()->index(true) as $page) {
            if ($page instanceof Page === false) {
                continue;
            }

            $document = $this->resolvePage($page, $languageCode);

            if ($document->sitemap->included === true && $document->sitemap->entry !== null) {
                $entries[] = $document->sitemap->entry;
            }
        }

        return $entries;
    }

    public function sitemapXml(?string $languageCode = null): string
    {
        $languageCode ??= $this->languageCode();
        $cache = SeoSitemapCache::for($this->kirby);
        $cachedXml = $cache->get($languageCode);

        if ($cachedXml !== null) {
            return $cachedXml;
        }

        $generator = $this->options()->sitemapGenerator([$this->kirby->site(), $languageCode]);

        if (is_string($generator) === true && trim($generator) !== '') {
            return $cache->set($generator, $languageCode);
        }

        return $cache->set($this->sitemapXmlRenderer->render($this->sitemapEntries($languageCode)), $languageCode);
    }

    private function options(): SeoOptionReader
    {
        return $this->options ?? new SeoOptionReader($this->kirby);
    }

    private function cacheKey(string $suffix): string
    {
        return spl_object_id($this->kirby) . ':' . $suffix;
    }

    private function pageContext(Page $page, string $languageCode): SeoContext
    {
        $language = $this->language($languageCode);

        return new SeoContext(
            documentType: SeoDocumentType::Page,
            id: $page->id(),
            url: $page->url($languageCode),
            language: $languageCode,
            languageName: $language?->name(),
            siteUrl: $this->kirby->site()->url($languageCode),
            template: $page->intendedTemplate()->name(),
            visibility: $this->visibility($page),
            isHomepage: $page->isHomePage(),
            isMultilingual: $this->kirby->multilang(),
        );
    }

    private function siteContext(Site $site, string $languageCode): SeoContext
    {
        $language = $this->language($languageCode);

        return new SeoContext(
            documentType: SeoDocumentType::Site,
            id: 'site',
            url: $site->url($languageCode),
            language: $languageCode,
            languageName: $language?->name(),
            siteUrl: $site->url($languageCode),
            template: 'site',
            visibility: SeoVisibility::Listed,
            isHomepage: true,
            isMultilingual: $this->kirby->multilang(),
        );
    }

    private function pageSnapshot(Page $page, SeoContext $context): SeoContentSnapshot
    {
        return $this->enrichedSnapshot(
            model: $page,
            context: $context,
            snapshot: new SeoContentSnapshot(
                title: $this->contentValue($page, 'title', $context->language) ?? $page->title()->value(),
                seoTitle: $this->contentValue($page, SeoFields::TITLE, $context->language),
                description: $this->contentValue($page, 'description', $context->language),
                seoDescription: $this->contentValue($page, SeoFields::DESCRIPTION, $context->language),
                excerpt: $this->excerpt($page, $context->language),
                imageUrl: $this->imageUrl($page, SeoFields::IMAGE, $context->language),
                canonicalUrl: $this->contentValue($page, SeoFields::CANONICAL, $context->language),
                translationUrls: $this->translationUrls($page),
                modifiedAt: $this->modifiedAt($page->modified()),
                searchAllowed: $this->searchAllowed($page, $context->language),
                metaTags: [],
            ),
        );
    }

    private function siteSnapshot(Site $site, SeoContext $context): SeoContentSnapshot
    {
        return $this->enrichedSnapshot(
            model: $site,
            context: $context,
            snapshot: new SeoContentSnapshot(
                title: $this->contentValue($site, 'title', $context->language) ?? $site->title()->value(),
                seoTitle: $this->contentValue($site, SeoFields::TITLE, $context->language),
                description: $this->contentValue($site, 'description', $context->language),
                seoDescription: $this->contentValue($site, SeoFields::DESCRIPTION, $context->language),
                excerpt: $this->contentValue($site, 'description', $context->language),
                imageUrl: $this->imageUrl($site, SeoFields::IMAGE, $context->language),
                canonicalUrl: $this->contentValue($site, SeoFields::CANONICAL, $context->language),
                translationUrls: $this->translationUrls($site),
                modifiedAt: null,
                searchAllowed: $this->searchAllowed($site, $context->language),
                metaTags: [],
            ),
        );
    }

    private function enrichedSnapshot(
        ModelWithContent $model,
        SeoContext $context,
        SeoContentSnapshot $snapshot,
    ): SeoContentSnapshot {
        $title = $this->options()->metaTitle(
            [$context, $snapshot->effectiveTitle(), $model],
            $snapshot->seoTitle ?? $snapshot->title,
        );
        $description = $this->options()->metaDescription(
            [$context, $snapshot->effectiveDescription(), $model],
            $snapshot->seoDescription ?? $snapshot->description ?? $snapshot->excerpt,
        );
        $canonical = $this->options()->metaCanonical([
            $context,
            $snapshot->canonicalUrl ?? $context->url,
            $model,
        ], $snapshot->canonicalUrl);
        $image = $this->options()->metaImage([$context, $snapshot->imageUrl, $model], $snapshot->imageUrl);
        $alternates = $this->options()->metaAlternates([
            $context,
            $snapshot->translationUrls,
            $model,
        ], $snapshot->translationUrls);

        return new SeoContentSnapshot(
            title: $snapshot->title,
            seoTitle: $title,
            description: $snapshot->description,
            seoDescription: $description,
            excerpt: $snapshot->excerpt,
            imageUrl: $image,
            canonicalUrl: $canonical,
            translationUrls: $alternates,
            modifiedAt: $snapshot->modifiedAt,
            searchAllowed: $snapshot->searchAllowed,
            metaTags: $snapshot->metaTags,
        );
    }

    private function rulesFor(ModelWithContent $model, SeoContext $context, SeoContentSnapshot $snapshot): SeoRuleSet
    {
        $defaults = $this->options()->metaDefaults([$context, $model, $snapshot]);
        $indexable = $this->options()->resolve('robots.index', [$context, $model, $snapshot], null);
        $sitemapIncluded = $model instanceof Page
            ? $this->options()->resolve('sitemap.include', [$context, $model, $snapshot], null)
            : true;
        $robotsMeta = $this->options()->robotsMeta([$context, [], $model, $snapshot], []);

        return new SeoRuleSet(
            titleSuffix: $this->stringFrom($defaults, 'titleSuffix'),
            defaultDescription: $this->stringFrom($defaults, 'defaultDescription'),
            canonicalUrl: $this->stringFrom($defaults, 'canonicalUrl'),
            metaTags: $this->arrayFrom($defaults, 'metaTags'),
            indexable: is_bool($indexable) ? $indexable : null,
            templateExcludedFromIndex: (bool) ($defaults['templateExcludedFromIndex'] ?? false),
            sitemapIncluded: is_bool($sitemapIncluded) ? $sitemapIncluded : null,
            templateExcludedFromSitemap: $model instanceof Page
            && in_array(
                $model->intendedTemplate()->name(),
                $this->options()->sitemapIgnoreTemplates([$context, $model, $snapshot]),
                true,
            ),
            sitemapRequireCanonical: (bool) ($defaults['sitemapRequireCanonical'] ?? true),
            sitemapAllowedVisibilities: $this->sitemapAllowedVisibilities($defaults),
            robotsWhenIndexable: $this->robotDirectives($robotsMeta, ['index', 'follow'], 'whenIndexable'),
            robotsWhenBlocked: $this->robotDirectives($robotsMeta, ['noindex', 'nofollow'], 'whenBlocked'),
        );
    }

    private function applyPostResolveFilters(
        ModelWithContent $model,
        SeoContext $context,
        SeoResolvedDocument $document,
    ): SeoResolvedDocument {
        $robotsDirectives = $this->options()->robotsMeta([
            $context,
            $document->meta->robotsDirectives,
            $model,
            $document,
        ], $document->meta->robotsDirectives);
        $metaTags = $this->options()->metaTags([
            $context,
            $document->meta->metaTags,
            $model,
            $document,
        ], $document->meta->metaTags);

        $meta = new SeoResolvedMeta(
            title: $document->meta->title,
            description: $document->meta->description,
            canonicalUrl: $document->meta->canonicalUrl,
            alternates: $document->meta->alternates,
            robotsDirectives: is_array($robotsDirectives) ? $robotsDirectives : $document->meta->robotsDirectives,
            metaTags: is_array($metaTags) ? $metaTags : $document->meta->metaTags,
            imageUrl: $document->meta->imageUrl,
        );

        return new SeoResolvedDocument(
            context: $document->context,
            snapshot: $document->snapshot,
            rules: $document->rules,
            meta: $meta,
            state: $document->state,
            sitemap: $document->sitemap,
        );
    }

    /**
     * @return array<string, string>
     */
    private function translationUrls(Page|Site $model): array
    {
        if ($this->kirby->multilang() === false) {
            return [];
        }

        $urls = [];

        foreach ($this->kirby->languages() as $language) {
            $code = $language->code();

            if ($model instanceof Page) {
                if ($model->translation($code)->exists() === false) {
                    continue;
                }

                $urls[$code] = $model->url($code);
                continue;
            }

            $urls[$code] = $model->url($code);
        }

        ksort($urls);

        return $urls;
    }

    private function searchAllowed(ModelWithContent $model, string $languageCode): ?bool
    {
        foreach ($this->contentFieldValues(
            $model,
            SeoFields::contentCandidates(SeoFields::INDEX),
            $languageCode,
        ) as $field => $value) {
            $allowed = $this->normalizeIndexValue($field, $value);

            if ($allowed !== null) {
                return $allowed;
            }
        }

        return null;
    }

    private function excerpt(Page $page, string $languageCode): ?string
    {
        $text = $this->contentValue($page, 'text', $languageCode);

        if ($text === null) {
            return null;
        }

        return \Kirby\Toolkit\Str::excerpt($text, 160);
    }

    private function contentValue(ModelWithContent $model, string $field, ?string $languageCode = null): ?string
    {
        foreach ($this->contentFieldValues($model, $this->fieldCandidates($field), $languageCode) as $value) {
            if (trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function imageUrl(ModelWithContent $model, string $field, ?string $languageCode = null): ?string
    {
        $languageCode ??= $this->languageCode();

        foreach ($this->contentFields($model, $this->fieldCandidates($field), $languageCode) as $fieldObject) {
            $file = $fieldObject->toFile();

            if ($file !== null) {
                try {
                    return $file->thumb(self::META_IMAGE_THUMB_PRESET)->url();
                } catch (Throwable) {
                    return $file->url();
                }
            }

            $value = $fieldObject->value();

            if ($value === '') {
                continue;
            }

            $file = $model->file($value);

            if ($file !== null) {
                try {
                    return $file->thumb(self::META_IMAGE_THUMB_PRESET)->url();
                } catch (Throwable) {
                    return $file->url();
                }
            }

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function fieldCandidates(string $field): array
    {
        return SeoFields::contentCandidates($field);
    }

    /**
     * @return \Kirby\Content\Field[]
     */
    private function contentFields(ModelWithContent $model, array $fields, ?string $languageCode = null): array
    {
        $languageCode ??= $this->languageCode();
        $fieldObjects = [];

        foreach ($fields as $field) {
            $fieldObjects[] = $model->content($languageCode)->get($field);
            $fieldObjects[] = $model->content()->get($field);
        }

        return $fieldObjects;
    }

    /**
     * @return array<string, string>
     */
    private function contentFieldValues(ModelWithContent $model, array $fields, ?string $languageCode = null): array
    {
        $values = [];

        foreach ($this->contentFields($model, $fields, $languageCode) as $fieldObject) {
            $key = $fieldObject->key();
            $value = $fieldObject->value();

            if (is_string($value) === false || trim($value) === '') {
                continue;
            }

            $values[$key] ??= trim($value);
        }

        return $values;
    }

    private function normalizeIndexValue(string $field, string $value): ?bool
    {
        $normalized = strtolower(trim($value));

        if ($normalized === '') {
            return null;
        }

        if ($field === 'robots' || str_contains($normalized, 'noindex') || str_contains($normalized, 'index')) {
            if (str_contains($normalized, 'noindex')) {
                return false;
            }

            if (str_contains($normalized, 'index')) {
                return true;
            }
        }

        return match ($normalized) {
            '1', 'true', 'yes' => true,
            '0', 'false', 'no' => false,
            default => null,
        };
    }

    private function languageCode(): string
    {
        return $this->kirby->language()?->code() ?? $this->kirby->defaultLanguage()?->code() ?? 'en';
    }

    private function language(?string $languageCode = null): ?\Kirby\Cms\Language
    {
        $languageCode ??= $this->languageCode();

        return $this->kirby->language($languageCode) ?? $this->kirby->defaultLanguage();
    }

    private function visibility(Page $page): SeoVisibility
    {
        return match (true) {
            $page->isDraft() => SeoVisibility::Draft,
            $page->isUnlisted() => SeoVisibility::Unlisted,
            default => SeoVisibility::Listed,
        };
    }

    private function modifiedAt(int|false $timestamp): ?DateTimeImmutable
    {
        if ($timestamp === false || $timestamp <= 0) {
            return null;
        }

        return new DateTimeImmutable('@' . $timestamp);
    }

    private function robotsSitemapUrl(SeoContext $context): string
    {
        return (
            $this->options()->robotsTxtSitemap([$context, $this->kirby->site()], $context->siteUrl . '/sitemap.xml')
            ?? $context->siteUrl . '/sitemap.xml'
        );
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private function defaultRobotsGroups(): array
    {
        return [
            '*' => [
                'Allow' => ['/media'],
                'Disallow' => ['/kirby', '/panel', '/content'],
            ],
        ];
    }

    /**
     * @param array<string, array<string, list<string>>> $groups
     * @return list<SeoRobotsRuleGroup>
     */
    private function ruleGroups(array $groups): array
    {
        $items = [];

        foreach ($groups as $userAgent => $rules) {
            $items[] = new SeoRobotsRuleGroup(
                userAgent: $userAgent,
                allow: array_values($rules['Allow'] ?? []),
                disallow: array_values($rules['Disallow'] ?? []),
                comments: array_values($rules['Comments'] ?? []),
            );
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $defaults
     * @return list<string>
     */
    private function sitemapAllowedVisibilities(array $defaults): array
    {
        $configured = $defaults['sitemapAllowedVisibilities'] ?? [SeoVisibility::Listed->value];

        return is_array($configured) ? array_values($configured) : [SeoVisibility::Listed->value];
    }

    /**
     * @param array<mixed> $meta
     * @return list<string>
     */
    private function robotDirectives(array $meta, array $fallback, string $key): array
    {
        $directives = $meta[$key] ?? $fallback;

        return is_array($directives) ? array_values($directives) : $fallback;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function stringFrom(array $values, string $key): ?string
    {
        $value = $values[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, string>
     */
    private function arrayFrom(array $values, string $key): array
    {
        $value = $values[$key] ?? [];

        return is_array($value) ? $value : [];
    }
}
