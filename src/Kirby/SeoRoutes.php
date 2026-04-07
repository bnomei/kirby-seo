<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby;

use Kirby\Cms\App;
use Kirby\Http\Response;

final class SeoRoutes
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function publicRoutes(): array
    {
        return [
            [
                'pattern' => 'robots.txt',
                'action' => function () {
                    $kirby = App::instance();
                    $body = SeoRuntimeBridge::for($kirby)->robotsTxt();

                    return new Response($body, 'text/plain');
                },
            ],
            [
                'pattern' => 'sitemap.xml',
                'action' => function () {
                    $kirby = App::instance();
                    $body = SeoRuntimeBridge::for($kirby)->sitemapXml();

                    return new Response($body, 'application/xml');
                },
            ],
        ];
    }
}
