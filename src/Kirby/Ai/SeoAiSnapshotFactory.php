<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Ai;

use Bnomei\Seo\Core\Ai\SeoAiSnapshot;
use Bnomei\Seo\Kirby\SeoOptionReader;
use DateTimeImmutable;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Toolkit\Str;

final class SeoAiSnapshotFactory
{
    public function __construct(
        private readonly SeoAiSourceExtractor $sourceExtractor = new SeoAiSourceExtractor(),
    ) {}

    public function build(Page|Site $model, string $languageCode): SeoAiSnapshot
    {
        $language = $model->kirby()->language($languageCode) ?? $model->kirby()->defaultLanguage();
        $text = (new SeoOptionReader($model->kirby()))->aiSource([
            $model,
            $languageCode,
            $model->kirby(),
        ]) ?? $this->sourceExtractor->extract($model, $languageCode);
        $excerpt = $text !== ''
            ? (string) Str::excerpt($text, 240)
            : (string) ($this->contentValue($model, 'description', $languageCode) ?? '');
        $languageName = $language?->name();
        $languageName = is_string($languageName) && trim($languageName) !== '' ? $languageName : $languageCode;

        return SeoAiSnapshot::fromArray([
            'kind' => $model instanceof Page ? 'page' : 'site',
            'id' => $model instanceof Page ? (string) $model->id() : 'site',
            'slug' => $model instanceof Page ? (string) $model->slug() : 'site',
            'uri' => $model instanceof Page ? (string) $model->uri() : 'site',
            'template' => $model instanceof Page ? (string) $model->intendedTemplate()->name() : 'site',
            'url' => (string) $model->url($languageCode),
            'title' => (string) ($this->contentValue($model, 'title', $languageCode) ?? $model->title()->value()),
            'excerpt' => $excerpt,
            'text' => $text,
            'fields' => $this->contentFields($model, $languageCode),
            'languageCode' => $languageCode,
            'languageName' => $languageName,
            'visibility' => $this->visibility($model),
            'updatedAt' => $this->updatedAt($model),
            'alternateUrls' => $this->alternateUrls($model),
            'aiTitle' => $this->contentValue($model, 'seoTitle', $languageCode),
            'aiDescription' => $this->contentValue($model, 'seoDescription', $languageCode),
            'aiState' => $this->contentValue($model, 'seoAiState', $languageCode) ?? 'auto',
            'aiSourceHash' => $this->contentValue($model, 'seoAiSourceHash', $languageCode),
            'aiGeneratedAt' => $this->dateValue($model, 'seoAiGeneratedAt', $languageCode),
            'aiPromptVersion' => $this->contentValue($model, 'seoAiPromptVersion', $languageCode),
            'aiLanguage' => $this->contentValue($model, 'seoAiLanguage', $languageCode),
            'seoIndexOverride' => $this->indexOverride($model, $languageCode),
            'canonicalUrl' => $this->contentValue($model, 'seoCanonical', $languageCode),
        ]);
    }

    private function contentValue(Page|Site $model, string $field, string $languageCode): ?string
    {
        $value = $model->content($languageCode)->get($field)->value();

        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        $fallback = $model->content()->get($field)->value();

        if (is_string($fallback) && trim($fallback) !== '') {
            return trim($fallback);
        }

        return null;
    }

    /**
     * @return array<string, scalar|array|null>
     */
    private function contentFields(Page|Site $model, string $languageCode): array
    {
        $values = $model->content($languageCode)->toArray();

        if (is_array($values) === false) {
            return [];
        }

        $fields = [];

        foreach ($values as $key => $value) {
            if (is_string($key) === false) {
                continue;
            }

            if (is_scalar($value) || is_array($value) || $value === null) {
                $fields[$key] = $value;
            }
        }

        return $fields;
    }

    /**
     * @return array<string, string>
     */
    private function alternateUrls(Page|Site $model): array
    {
        if ($model->kirby()->multilang() === false) {
            return [];
        }

        $urls = [];

        foreach ($model->kirby()->languages() as $language) {
            $code = $language->code();

            if ($model instanceof Page && $model->translation($code)->exists() === false) {
                continue;
            }

            $urls[$code] = $model->url($code);
        }

        ksort($urls);

        return $urls;
    }

    private function visibility(Page|Site $model): string
    {
        if ($model instanceof Site) {
            return 'listed';
        }

        return match (true) {
            $model->isDraft() => 'draft',
            $model->isListed() => 'listed',
            default => 'unlisted',
        };
    }

    private function updatedAt(Page|Site $model): ?DateTimeImmutable
    {
        if ($model instanceof Page === false) {
            return null;
        }

        $timestamp = $model->modified();

        if ($timestamp === false || $timestamp <= 0) {
            return null;
        }

        return new DateTimeImmutable('@' . $timestamp);
    }

    private function dateValue(Page|Site $model, string $field, string $languageCode): ?DateTimeImmutable
    {
        $value = $this->contentValue($model, $field, $languageCode);

        if ($value === null) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function indexOverride(Page|Site $model, string $languageCode): string
    {
        return match ($this->contentValue($model, 'seoIndex', $languageCode)) {
            '1', 'true', 'yes' => 'allow',
            '0', 'false', 'no' => 'hide',
            default => 'default',
        };
    }
}
