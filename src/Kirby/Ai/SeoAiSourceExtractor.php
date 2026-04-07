<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Ai;

use Kirby\Cms\Page;
use Kirby\Cms\Site;

final class SeoAiSourceExtractor
{
    public function __construct(
        private readonly SeoAiPageRenderer $pageRenderer = new SeoAiPageRenderer(),
        private readonly SeoAiHtmlToMarkdownConverter $markdownConverter = new SeoAiHtmlToMarkdownConverter(),
    ) {}

    public function extract(Page|Site $model, string $languageCode): string
    {
        $markdown = $model instanceof Page
            ? $this->pageMarkdown($model, $languageCode)
            : $this->siteMarkdown($model, $languageCode);

        if ($markdown !== '') {
            return $markdown;
        }

        return $this->fallbackSource($model, $languageCode);
    }

    private function siteMarkdown(Site $site, string $languageCode): string
    {
        try {
            $homePage = $site->homePage();
        } catch (\Throwable) {
            return '';
        }

        return $this->pageMarkdown($homePage, $languageCode);
    }

    private function pageMarkdown(Page $page, string $languageCode): string
    {
        $html = $this->pageRenderer->render($page, $languageCode);

        if ($html === '') {
            return '';
        }

        return $this->markdownConverter->convert($html);
    }

    private function fallbackSource(Page|Site $model, string $languageCode): string
    {
        return (string) (
            $this->contentValue($model, 'text', $languageCode) ?? $this->contentValue(
                $model,
                'description',
                $languageCode,
            ) ?? ''
        );
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
}
