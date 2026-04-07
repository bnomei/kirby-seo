<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby;

use Bnomei\Seo\Core\Value\SeoIndexabilityReason;
use Bnomei\Seo\Core\Value\SeoResolvedDocument;
use Bnomei\Seo\Core\Value\SeoSitemapReason;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Toolkit\I18n;

final class SeoMethodRegistry
{
    /**
     * @return array<string, \Closure>
     */
    public static function pageMethods(): array
    {
        return [
            'seoResolvedDocument' => function (): SeoResolvedDocument {
                return SeoRuntimeBridge::for($this->kirby())->resolvePage($this);
            },
            'seoHeadTags' => function (): string {
                return SeoRuntimeBridge::for($this->kirby())->headTagsFor($this);
            },
            'seoPreviewTitle' => function (): string {
                return SeoRuntimeBridge::for($this->kirby())->resolvePage($this)->meta->title;
            },
            'seoPreviewDescription' => function (): string {
                return SeoRuntimeBridge::for($this->kirby())->resolvePage($this)->meta->description;
            },
            'seoPreviewUrl' => function (): string {
                return SeoRuntimeBridge::for($this->kirby())->resolvePage($this)->meta->canonicalUrl ?? $this->url();
            },
            'seoCanonical' => function (): string {
                return SeoRuntimeBridge::for($this->kirby())->resolvePage($this)->meta->canonicalUrl ?? '';
            },
            'seoAiStateLabel' => function (): string {
                return (
                    I18n::translate(
                        'bnomei.seo.status.ai.' . ((string) $this->content()->get('seoAiState')->value() ?: 'auto'),
                        'Auto',
                    ) ?? 'Auto'
                );
            },
            'seoIndexStatus' => function (): string {
                return SeoRuntimeBridge::for($this->kirby())->resolvePage($this)->state->indexable
                    ? SeoMethodRegistry::translate('bnomei.seo.status.index.allowed')
                    : SeoMethodRegistry::translate('bnomei.seo.status.index.blocked');
            },
            'seoSitemapStatus' => function (): string {
                return SeoRuntimeBridge::for($this->kirby())->resolvePage($this)->sitemap->included
                    ? SeoMethodRegistry::translate('bnomei.seo.status.sitemap.included')
                    : SeoMethodRegistry::translate('bnomei.seo.status.sitemap.excluded');
            },
            'seoLanguageLabel' => function (): string {
                $language = $this->kirby()->language() ?? $this->kirby()->defaultLanguage();

                if ($language === null) {
                    return '';
                }

                return $language->name() . ' (' . $language->code() . ')';
            },
            'seoStatusReason' => function (): string {
                return SeoMethodRegistry::reasonFor(SeoRuntimeBridge::for($this->kirby())->resolvePage($this));
            },
        ];
    }

    /**
     * @return array<string, \Closure>
     */
    public static function siteMethods(): array
    {
        return [
            'seoResolvedDocument' => function (): SeoResolvedDocument {
                return SeoRuntimeBridge::for($this->kirby())->resolveSite($this);
            },
            'seoHeadTags' => function (): string {
                return SeoRuntimeBridge::for($this->kirby())->headTagsFor($this);
            },
            'seoRobotsTxt' => function (): string {
                return SeoRuntimeBridge::for($this->kirby())->robotsTxt();
            },
            'seoSitemapXml' => function (): string {
                return SeoRuntimeBridge::for($this->kirby())->sitemapXml();
            },
            'seoPreviewTitle' => function (): string {
                return SeoRuntimeBridge::for($this->kirby())->resolveSite($this)->meta->title;
            },
            'seoPreviewDescription' => function (): string {
                return SeoRuntimeBridge::for($this->kirby())->resolveSite($this)->meta->description;
            },
            'seoPreviewUrl' => function (): string {
                return SeoRuntimeBridge::for($this->kirby())->resolveSite($this)->meta->canonicalUrl ?? $this->url();
            },
            'seoCanonical' => function (): string {
                return SeoRuntimeBridge::for($this->kirby())->resolveSite($this)->meta->canonicalUrl ?? '';
            },
            'seoAiStateLabel' => function (): string {
                return (
                    I18n::translate(
                        'bnomei.seo.status.ai.' . ((string) $this->content()->get('seoAiState')->value() ?: 'auto'),
                        'Auto',
                    ) ?? 'Auto'
                );
            },
            'seoIndexStatus' => function (): string {
                return SeoRuntimeBridge::for($this->kirby())->resolveSite($this)->state->indexable
                    ? SeoMethodRegistry::translate('bnomei.seo.status.index.allowed')
                    : SeoMethodRegistry::translate('bnomei.seo.status.index.blocked');
            },
            'seoSitemapStatus' => function (): string {
                return SeoRuntimeBridge::for($this->kirby())->resolveSite($this)->sitemap->included
                    ? SeoMethodRegistry::translate('bnomei.seo.status.sitemap.included')
                    : SeoMethodRegistry::translate('bnomei.seo.status.sitemap.excluded');
            },
            'seoLanguageLabel' => function (): string {
                $language = $this->kirby()->language() ?? $this->kirby()->defaultLanguage();

                if ($language === null) {
                    return '';
                }

                return $language->name() . ' (' . $language->code() . ')';
            },
            'seoStatusReason' => function (): string {
                return SeoMethodRegistry::reasonFor(SeoRuntimeBridge::for($this->kirby())->resolveSite($this));
            },
        ];
    }

    public static function reasonFor(SeoResolvedDocument $document): string
    {
        $key = match (true) {
            $document->state->indexabilityReason === SeoIndexabilityReason::Draft => 'bnomei.seo.reason.page-draft',
            $document->state->indexabilityReason === SeoIndexabilityReason::TemplateExcluded
                => 'bnomei.seo.reason.template-excluded',
            $document->state->indexabilityReason === SeoIndexabilityReason::ConfigBlocked
                => 'bnomei.seo.reason.index-disabled',
            $document->state->indexabilityReason === SeoIndexabilityReason::ManualBlocked
                => 'bnomei.seo.reason.index-disabled',
            $document->sitemap->reason === SeoSitemapReason::Draft => 'bnomei.seo.reason.page-draft',
            $document->sitemap->reason === SeoSitemapReason::VisibilityExcluded => 'bnomei.seo.reason.page-unlisted',
            $document->sitemap->reason === SeoSitemapReason::IndexingBlocked => 'bnomei.seo.reason.index-disabled',
            $document->sitemap->reason === SeoSitemapReason::TemplateExcluded => 'bnomei.seo.reason.template-excluded',
            $document->sitemap->reason === SeoSitemapReason::MissingCanonical => 'bnomei.seo.reason.missing-canonical',
            $document->context->documentType->value === 'site' => 'bnomei.seo.reason.site-defaults',
            default => 'bnomei.seo.reason.included',
        };

        return self::translate($key);
    }

    public static function translate(string $key): string
    {
        return I18n::translate($key, $key) ?? $key;
    }
}
