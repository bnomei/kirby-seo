<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Panel;

use Bnomei\Seo\Kirby\SeoRuntimeBridge;
use Kirby\Cms\Page;
use Kirby\Cms\Site;

final class SeoPanelButtons
{
    private const BUTTON_CLASS = 'k-seo-view-button';

    public static function seo(Page|Site $model): array
    {
        $options = $model instanceof Page ? 'seo/page/' . $model->id() : 'seo/site';

        return [
            'class' => self::buttonClass($model),
            'icon' => 'seo',
            'text' => 'bnomei.seo.button.label',
            'options' => $options,
        ];
    }

    public static function page(Page $page): array
    {
        return self::seo($page);
    }

    public static function site(Site $site): array
    {
        return self::seo($site);
    }

    private static function buttonClass(Page|Site $model): string
    {
        return self::BUTTON_CLASS . ' ' . self::BUTTON_CLASS . '--' . (self::isHealthy($model) ? 'ok' : 'problem');
    }

    private static function isHealthy(Page|Site $model): bool
    {
        $bridge = SeoRuntimeBridge::for($model->kirby());
        $document = $model instanceof Page ? $bridge->resolvePage($model) : $bridge->resolveSite($model);

        return $document->state->indexable === true && $document->sitemap->included === true;
    }
}
