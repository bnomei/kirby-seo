<?php

declare(strict_types=1);

use Bnomei\Seo\Kirby\SeoSitemapCache;
use Kirby\Cache\FileCache;
use Kirby\Filesystem\Dir;
use Kirby\Http\Response;

it('caches sitemap xml and flushes it after content mutations', function (): void {
    $sitemapCalls = 0;
    $kirby = testKirby([
        'options' => [
            'bnomei.seo.ai.generate' => static fn (): array => [
                'title' => 'Generated title',
                'description' => 'Generated description',
            ],
            'bnomei.seo.sitemap.generator' => static function ($site, $languageCode) use (&$sitemapCalls): string {
                unset($site);
                $sitemapCalls++;

                return '<?xml version="1.0" encoding="UTF-8"?>'
                    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
                    . '<url><loc>https://example.test/' . $languageCode . '/' . $sitemapCalls . '</loc></url>'
                    . '</urlset>';
            },
        ],
    ]);

    $first = $kirby->call('sitemap.xml');
    $second = $kirby->call('sitemap.xml');

    expect($first)->toBeInstanceOf(Response::class);
    expect($second)->toBeInstanceOf(Response::class);
    expect($first->body())->toBe($second->body());
    expect($sitemapCalls)->toBe(1);

    $page = $kirby->site()->homePage();
    $kirby->impersonate('kirby', fn () => $page->update(['text' => 'Updated body'], 'en', false));

    $third = $kirby->call('sitemap.xml');

    expect($third)->toBeInstanceOf(Response::class);
    expect($third->body())->not->toBe($first->body());
    expect($sitemapCalls)->toBe(2);
});

it('flushes sitemap cache safely when the file cache root was already removed', function (): void {
    $kirby = testKirby();
    $cache = $kirby->cache('bnomei.seo.sitemap');

    expect($cache)->toBeInstanceOf(FileCache::class);

    $cache->set('xml:en', '<xml />', 5);
    Dir::remove($cache->root());

    $warnings = [];
    $previous = set_error_handler(static function (int $severity, string $message) use (&$warnings): bool {
        $warnings[] = [$severity, $message];

        return true;
    });

    try {
        SeoSitemapCache::for($kirby)->flush();
    } finally {
        restore_error_handler();
    }

    expect($warnings)->toBe([]);
    expect($previous)->not->toBeFalse();
});
