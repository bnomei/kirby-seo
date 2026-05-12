<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Panel;

use Bnomei\Seo\Kirby\SeoMethodRegistry;
use Bnomei\Seo\Kirby\SeoRuntimeBridge;
use Kirby\Cms\Page;
use Kirby\Cms\Site;

final class SeoPanelActionDropdown
{
    public static function for(Page|Site $model): array
    {
        $aiState = self::aiState($model);
        $canUpdate = SeoPanelActionAccess::canUpdate($model);
        $document = $model instanceof Page
            ? SeoRuntimeBridge::for($model->kirby())->resolvePage($model)
            : SeoRuntimeBridge::for($model->kirby())->resolveSite($model);

        $actions = [
            'indexStatus' => self::infoRow(
                SeoMethodRegistry::translate('bnomei.seo.status.index'),
                $model->seoIndexStatus(),
                'robot',
                $document->state->indexable === true ? 'ok' : 'problem',
            ),
            'sitemapStatus' => self::infoRow(
                SeoMethodRegistry::translate('bnomei.seo.status.sitemap'),
                $model->seoSitemapStatus(),
                'sitemap',
                $document->sitemap->included === true ? 'ok' : 'problem',
            ),
            '-',
            'generate' => [
                'icon' => 'ai',
                'text' => SeoMethodRegistry::translate('bnomei.seo.button.generate'),
                'link' => SeoPanelActionAccess::link($model, 'generate'),
                'disabled' => $canUpdate === false,
            ],
        ];

        $actions[] = '-';

        $actions += [
            'unlock' => [
                'icon' => 'unlock',
                'text' => SeoMethodRegistry::translate('bnomei.seo.button.unlock'),
                'link' => SeoPanelActionAccess::link($model, 'unlock'),
                'disabled' => $canUpdate === false || $aiState !== 'locked',
            ],
            'lock' => [
                'icon' => 'lock',
                'text' => SeoMethodRegistry::translate('bnomei.seo.button.lock'),
                'link' => SeoPanelActionAccess::link($model, 'lock'),
                'disabled' => $canUpdate === false || $aiState === 'locked',
            ],
        ];

        if ($model instanceof Page) {
            $index = $model->content()->get('seoIndex')->value();
            $index = is_string($index) ? trim($index) : '';

            $actions[] = '-';
            $actions['allow'] = [
                'icon' => 'preview',
                'text' => SeoMethodRegistry::translate('bnomei.seo.button.allow'),
                'link' => SeoPanelActionAccess::link($model, 'allow'),
                'disabled' => $canUpdate === false || $index === '1',
            ];
            $actions['hide'] = [
                'icon' => 'hidden',
                'text' => SeoMethodRegistry::translate('bnomei.seo.button.hide'),
                'link' => SeoPanelActionAccess::link($model, 'hide'),
                'disabled' => $canUpdate === false || $index === '0',
            ];
        }

        return $actions;
    }

    private static function aiState(Page|Site $model): string
    {
        $currentState = $model->content()->get('seoAiState')->value();
        $currentState = is_string($currentState) ? trim($currentState) : '';

        return $currentState === 'locked' ? 'locked' : 'auto';
    }

    private static function infoRow(string $label, string $value, string $icon, string $variant): array
    {
        return [
            'class' => 'k-seo-dropdown-status k-seo-dropdown-status--' . $variant,
            'disabled' => true,
            'icon' => $icon,
            'text' => $label . ': ' . $value,
        ];
    }
}
