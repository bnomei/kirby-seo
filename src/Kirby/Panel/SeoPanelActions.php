<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Panel;

use Bnomei\Seo\Kirby\Ai\SeoAiService;
use Bnomei\Seo\Kirby\SeoMethodRegistry;
use Bnomei\Seo\Kirby\SeoRuntimeBridge;
use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Panel\Panel;

final class SeoPanelActions
{
    public static function dropdown(Page|Site $model): array
    {
        $aiState = self::aiState($model);
        $document = $model instanceof Page
            ? SeoRuntimeBridge::for($model->kirby())->resolvePage($model)
            : SeoRuntimeBridge::for($model->kirby())->resolveSite($model);

        $actions = [
            'indexStatus' => self::infoRow(
                SeoMethodRegistry::translate('bnomei.seo.status.index'),
                $model->seoIndexStatus(),
                'robot',
                $document->state->indexable === true,
            ),
            'sitemapStatus' => self::infoRow(
                SeoMethodRegistry::translate('bnomei.seo.status.sitemap'),
                $model->seoSitemapStatus(),
                'sitemap',
                $document->sitemap->included === true,
            ),
            '-',
            'generate' => [
                'icon' => 'ai',
                'text' => SeoMethodRegistry::translate('bnomei.seo.button.generate'),
                'link' => self::actionPath($model, 'generate'),
            ],
        ];

        $actions[] = '-';

        $actions += [
            'unlock' => [
                'icon' => 'unlock',
                'text' => SeoMethodRegistry::translate('bnomei.seo.button.unlock'),
                'link' => self::actionPath($model, 'unlock'),
                'disabled' => $aiState !== 'locked',
            ],
            'lock' => [
                'icon' => 'lock',
                'text' => SeoMethodRegistry::translate('bnomei.seo.button.lock'),
                'link' => self::actionPath($model, 'lock'),
                'disabled' => $aiState === 'locked',
            ],
        ];

        if ($model instanceof Page) {
            $index = $model->content()->get('seoIndex')->value();
            $index = is_string($index) ? trim($index) : '';

            $actions[] = '-';
            $actions['allow'] = [
                'icon' => 'preview',
                'text' => SeoMethodRegistry::translate('bnomei.seo.button.allow'),
                'link' => self::actionPath($model, 'allow'),
                'disabled' => $index === '1',
            ];
            $actions['hide'] = [
                'icon' => 'hidden',
                'text' => SeoMethodRegistry::translate('bnomei.seo.button.hide'),
                'link' => self::actionPath($model, 'hide'),
                'disabled' => $index === '0',
            ];
        }

        return $actions;
    }

    public static function submit(Page|Site $model, string $action): array
    {
        $action = self::normalizeAction($model, $action);

        $kirby = App::instance();
        $language = $kirby->languageCode() ?? $kirby->defaultLanguage()?->code() ?? 'en';
        $service = SeoAiService::for($kirby);

        match ($action) {
            'generate' => $model instanceof Page
                ? $service->generatePage($model, $language)
                : $service->generateSite($model, $language),
            'lock' => $service->setAiState($model, 'locked', $language),
            'unlock' => $service->setAiState($model, 'auto', $language),
            'hide' => $service->setSearchIndexOverride($model, false, $language),
            'allow' => $service->setSearchIndexOverride($model, true, $language),
            default => $model,
        };

        return [
            'event' => $model instanceof Page ? 'page.update' : 'site.update',
        ];
    }

    public static function perform(Page|Site $model, string $action): never
    {
        self::submit($model, $action);
        Panel::go(self::redirectPath($model));
    }

    private static function actionPath(Page|Site $model, string $action): string
    {
        return $model instanceof Page
            ? 'seo/page/' . $model->id() . '/action/' . $action
            : 'seo/site/action/' . $action;
    }

    private static function redirectPath(Page|Site $model): string
    {
        return $model->panel()->url(true);
    }

    private static function normalizeAction(Page|Site $model, string $action): string
    {
        if ($action === 'refresh') {
            return 'generate';
        }

        if ($action !== 'toggleAiState') {
            return $action;
        }

        return self::aiState($model) === 'locked' ? 'unlock' : 'lock';
    }

    private static function aiState(Page|Site $model): string
    {
        $currentState = $model->content()->get('seoAiState')->value();
        $currentState = is_string($currentState) ? trim($currentState) : '';

        return $currentState === 'locked' ? 'locked' : 'auto';
    }

    private static function infoRow(string $label, string $value, string $icon, bool $ok): array
    {
        return [
            'class' => 'k-seo-dropdown-status k-seo-dropdown-status--' . ($ok ? 'ok' : 'problem'),
            'disabled' => true,
            'icon' => $icon,
            'text' => $label . ': ' . $value,
        ];
    }
}
