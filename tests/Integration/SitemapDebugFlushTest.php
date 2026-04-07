<?php

declare(strict_types=1);

use Kirby\Cms\App;
use Kirby\Filesystem\Dir;
use Kirby\Http\Response;

it('flushes a persisted sitemap cache on debug boot', function (): void {
    $root = dirname(__DIR__, 2);
    $fixture = $root . '/tests/site';
    $sandbox = sys_get_temp_dir() . '/kirby-seo-tests/' . bin2hex(random_bytes(8));
    $sitemapCalls = 0;

    Dir::make($sandbox, true);
    Dir::copy($fixture . '/content', $sandbox . '/content');
    Dir::make($sandbox . '/media', true);
    Dir::make($sandbox . '/cache', true);
    Dir::make($sandbox . '/sessions', true);
    Dir::make($sandbox . '/accounts', true);

    $props = [
        'roots' => [
            'index' => $root,
            'base' => $root,
            'kirby' => $root . '/tests/kirby',
            'site' => $fixture,
            'content' => $sandbox . '/content',
            'media' => $sandbox . '/media',
            'cache' => $sandbox . '/cache',
            'sessions' => $sandbox . '/sessions',
            'accounts' => $sandbox . '/accounts',
            'blueprints' => $fixture . '/blueprints',
            'languages' => $fixture . '/languages',
        ],
        'options' => [
            'debug' => true,
            'bnomei.seo.sitemap.generator' => static function ($site, $languageCode) use (&$sitemapCalls): string {
                unset($site);
                $sitemapCalls++;

                return '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
                    . '<url><loc>https://example.test/' . $languageCode . '/' . $sitemapCalls . '</loc></url>'
                    . '</urlset>';
            },
        ],
    ];

    $first = new App($props);
    $firstResponse = $first->call('sitemap.xml');

    expect($firstResponse)->toBeInstanceOf(Response::class);
    expect($sitemapCalls)->toBe(1);

    $second = new App($props);
    $secondResponse = $second->call('sitemap.xml');

    expect($secondResponse)->toBeInstanceOf(Response::class);
    expect($secondResponse->body())->not->toBe($firstResponse->body());
    expect($sitemapCalls)->toBe(2);
});
