<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Migration;

use Bnomei\Seo\Kirby\SeoFields;
use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Throwable;

final class FabianMetaMigrationAdapter extends AbstractMigrationAdapter
{
    public function source(): string
    {
        return 'fabianmeta';
    }

    public function label(): string
    {
        return 'Fabian Michael Meta';
    }

    protected function explicitValue(Page|Site $model, string $languageCode, string $targetField): ?string
    {
        return match ($targetField) {
            SeoFields::TITLE => $this->contentValue($model, $languageCode, 'meta_title'),
            SeoFields::DESCRIPTION => $this->contentValue($model, $languageCode, 'meta_description'),
            SeoFields::CANONICAL => $this->contentValue($model, $languageCode, 'meta_canonical_url'),
            SeoFields::IMAGE => $this->contentValue($model, $languageCode, 'og_image'),
            SeoFields::INDEX => $this->normalizedIndexValue($this->contentValue($model, $languageCode, 'robots_index')),
            default => null,
        };
    }

    protected function resolvedValue(Page|Site $model, string $languageCode, string $targetField): ?string
    {
        $meta = $this->metaObject($model, $languageCode);

        return match ($targetField) {
            SeoFields::TITLE => $this->metadataLookup($meta, ['title']),
            SeoFields::DESCRIPTION => $this->metadataLookup($meta, ['description']),
            SeoFields::CANONICAL => $this->metadataLookup($meta, ['canonicalUrl', 'canonical']),
            SeoFields::IMAGE => $this->metadataLookup($meta, ['image', 'imageUrl', 'ogImage']),
            SeoFields::INDEX => $this->normalizedIndexValue($this->metadataLookup($meta, [
                'robots',
                'robotsDirectives',
            ])),
            default => null,
        };
    }

    protected function warningsFor(Page|Site $model, string $languageCode): array
    {
        return $this->nonEmptyLegacyFields($model, $languageCode, [
            'og_title',
            'og_description',
            'robots_follow',
            'robots_archive',
            'robots_imageindex',
            'robots_snippet',
            'sitemap_priority',
            'sitemap_changefreq',
            'theme_color',
            'meta_title_separator',
            'schema_company_name',
            'schema_company_type',
            'schema_company_logo',
        ]);
    }

    protected function blueprintReplacements(): array
    {
        return [
            'tabs/meta/page' => 'tabs/seo/page',
            'tabs/meta/site' => 'tabs/seo/site',
        ];
    }

    protected function phpReplacements(): array
    {
        return [
            [
                'pattern' => '/snippet\(\s*([\'"])meta\1/',
                'replace' => 'snippet($1seo/head$1',
                'label' => "snippet('meta') -> snippet('seo/head')",
            ],
        ];
    }

    public function manualFollowUps(App $kirby): array
    {
        return array_values(array_unique([
            ...parent::manualFollowUps($kirby),
            ...$this->unsupportedSnippetWarnings($kirby),
        ]));
    }

    protected function optionWarnings(): array
    {
        return [
            'fabianmichael.meta.robots.follow' => 'Legacy option `fabianmichael.meta.robots.follow` requires manual review.',
            'fabianmichael.meta.robots.archive' => 'Legacy option `fabianmichael.meta.robots.archive` requires manual review.',
            'fabianmichael.meta.robots.imageindex' => 'Legacy option `fabianmichael.meta.robots.imageindex` requires manual review.',
            'fabianmichael.meta.sitemap' => 'Legacy option `fabianmichael.meta.sitemap` requires manual review.',
            'fabianmichael.meta.schema' => 'Legacy option `fabianmichael.meta.schema` requires manual review.',
        ];
    }

    private function metaObject(Page|Site $model, string $languageCode): mixed
    {
        if ($model->hasMethod('meta') === false) {
            return null;
        }

        try {
            return $model->meta($languageCode);
        } catch (Throwable) {
            try {
                return $model->meta();
            } catch (Throwable) {
                return null;
            }
        }
    }

    /**
     * @return string[]
     */
    private function unsupportedSnippetWarnings(App $kirby): array
    {
        $warnings = [];

        foreach ([$kirby->root('site') . '/templates', $kirby->root('site') . '/snippets'] as $root) {
            if (is_dir($root) === false) {
                continue;
            }

            foreach (Dir::index($root, true) as $relativePath) {
                $path = $root . '/' . $relativePath;

                if (is_file($path) === false) {
                    continue;
                }

                $content = F::read($path);

                if (preg_match('/snippet\(\s*([\'"])meta\//', $content) === 1) {
                    $warnings[] =
                        'Fabian Michael Meta file `' . $path . '` contains unsupported `meta/*` snippet calls.';
                }
            }
        }

        return $warnings;
    }
}
