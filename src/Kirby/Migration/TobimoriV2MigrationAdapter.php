<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Migration;

use Bnomei\Seo\Kirby\SeoFields;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Throwable;

final class TobimoriV2MigrationAdapter extends AbstractMigrationAdapter
{
    public function source(): string
    {
        return 'tobimori-v2';
    }

    public function label(): string
    {
        return 'Tobimori SEO current main / v2';
    }

    protected function explicitValue(Page|Site $model, string $languageCode, string $targetField): ?string
    {
        return match ($targetField) {
            SeoFields::TITLE => $this->contentValue($model, $languageCode, 'metaTitle'),
            SeoFields::DESCRIPTION => $this->contentValue($model, $languageCode, 'metaDescription'),
            SeoFields::IMAGE => $this->contentValue($model, $languageCode, 'ogImage'),
            SeoFields::INDEX => $this->normalizedIndexValue($this->contentValue($model, $languageCode, 'robots')),
            default => null,
        };
    }

    protected function resolvedValue(Page|Site $model, string $languageCode, string $targetField): ?string
    {
        $metadata = $this->metadata($model, $languageCode);

        return match ($targetField) {
            SeoFields::TITLE => $this->metadataLookup($metadata, ['title', 'metaTitle']),
            SeoFields::DESCRIPTION => $this->metadataLookup($metadata, ['description', 'metaDescription']),
            SeoFields::CANONICAL => $this->canonicalValue($model, $languageCode, $metadata),
            SeoFields::IMAGE => $this->metadataLookup($metadata, ['image', 'imageUrl', 'ogImage']),
            SeoFields::INDEX => $this->normalizedIndexValue(
                $this->metadataLookup($metadata, ['robots', 'robotsDirectives']) ?? $this->robotsValue(
                    $model,
                    $languageCode,
                ),
            ),
            default => null,
        };
    }

    protected function warningsFor(Page|Site $model, string $languageCode): array
    {
        return $this->nonEmptyLegacyFields($model, $languageCode, ['cropOgImage']);
    }

    protected function blueprintReplacements(): array
    {
        return [
            'seo/page' => 'tabs/seo/page',
            'seo/site' => 'tabs/seo/site',
        ];
    }

    protected function optionWarnings(): array
    {
        return [
            'tobimori.seo.canonical.base' => 'Legacy option `tobimori.seo.canonical.base` requires manual mapping into `bnomei.seo.*`.',
            'tobimori.seo.canonical.trailingSlash' => 'Legacy option `tobimori.seo.canonical.trailingSlash` requires manual review.',
            'tobimori.seo.locale' => 'Legacy option `tobimori.seo.locale` requires manual review.',
            'tobimori.seo.robots.enabled' => 'Legacy option `tobimori.seo.robots.enabled` should be reviewed after migration.',
            'tobimori.seo.sitemap.enabled' => 'Legacy option `tobimori.seo.sitemap.enabled` should be reviewed after migration.',
        ];
    }

    private function metadata(Page|Site $model, string $languageCode): mixed
    {
        if ($model->hasMethod('metadata') === false) {
            return null;
        }

        try {
            return $model->metadata($languageCode);
        } catch (Throwable) {
            try {
                return $model->metadata();
            } catch (Throwable) {
                return null;
            }
        }
    }

    private function robotsValue(Page|Site $model, string $languageCode): ?string
    {
        if ($model->hasMethod('robots') === false) {
            return null;
        }

        try {
            return $this->normalizeScalar($model->robots($languageCode));
        } catch (Throwable) {
            try {
                return $this->normalizeScalar($model->robots());
            } catch (Throwable) {
                return null;
            }
        }
    }

    private function canonicalValue(Page|Site $model, string $languageCode, mixed $metadata): ?string
    {
        $resolved = $this->metadataLookup($metadata, ['canonical', 'canonicalUrl', 'url']);

        if ($resolved !== null) {
            return $resolved;
        }

        if ($model instanceof Site && $model->hasMethod('canonicalFor') === true) {
            try {
                return $this->normalizeScalar($model->canonicalFor($model->url($languageCode)));
            } catch (Throwable) {
            }
        }

        return null;
    }
}
