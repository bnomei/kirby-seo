<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby;

use Bnomei\Seo\Kirby\Ai\SeoAiHooks;
use Kirby\Cms\App;

final class SeoHooks
{
    /**
     * @return array<string, \Closure>
     */
    public static function pluginHooks(): array
    {
        $flush = static function (): void {
            SeoSitemapCache::for(App::instance())->flush();
        };

        $flushOnly = function (mixed ...$arguments) use ($flush): void {
            unset($arguments);

            $flush();
        };

        return [
            'page.create:after' => $flushOnly,
            'page.delete:after' => $flushOnly,
            'page.duplicate:after' => $flushOnly,
            'page.changeNum:after' => $flushOnly,
            'page.changeSlug:after' => $flushOnly,
            'page.changeStatus:after' => $flushOnly,
            'page.changeTemplate:after' => $flushOnly,
            'page.changeTitle:after' => $flushOnly,
            'page.update:after' => function ($newPage, $oldPage) use ($flush): void {
                $flush();
                SeoAiHooks::pageUpdated($newPage, $oldPage);
            },
            'site.changeTitle:after' => $flushOnly,
            'site.update:after' => function ($newSite, $oldSite) use ($flush): void {
                $flush();
                SeoAiHooks::siteUpdated($newSite, $oldSite);
            },
            'language.create:after' => $flushOnly,
            'language.delete:after' => $flushOnly,
            'language.update:after' => $flushOnly,
        ];
    }
}
