<?php

declare(strict_types=1);

use Kirby\Http\Response;

it('renders public seo surfaces through the snippet and public routes', function (): void {
    $kirby = testKirby([
        'options' => [
            'bnomei.seo.meta.defaults' => static fn (): array => [
                'sitemapAllowedVisibilities' => ['listed', 'unlisted'],
            ],
        ],
    ]);
    $page = $kirby->site()->homePage();

    $head = snippet('seo/head', ['page' => $page], true);
    $robots = $kirby->call('robots.txt');
    $sitemap = $kirby->call('sitemap.xml');

    expect($head)->toContain('<title>');
    expect($head)->toContain('<meta name="description"');

    expect($robots)->toBeInstanceOf(Response::class);
    expect($robots->body())->toContain('User-agent: *');
    expect($robots->body())->toContain('Sitemap:');

    expect($sitemap)->toBeInstanceOf(Response::class);
    expect($sitemap->body())->toContain('<urlset');
    expect($sitemap->body())->toContain('<loc>');
});
