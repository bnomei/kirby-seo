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

it('allows the robots txt filter to inspect and modify structured directives', function (): void {
    $inspected = null;
    $kirby = testKirby([
        'options' => [
            'bnomei.seo.robots.txt.groups' => [
                '*' => [
                    'Directives' => [
                        'Content-Signal' => 'ai-train=no',
                    ],
                    'Disallow' => ['/panel'],
                ],
            ],
            'bnomei.seo.robots.txt.filter' => static function ($context, array $groups) use (&$inspected): array {
                $inspected = $groups['*']['Directives'];
                $groups['*']['Directives']['Content-Signal'] = ['ai-train=yes', 'search=yes'];

                return $groups;
            },
        ],
    ]);

    $robots = $kirby->call('robots.txt');

    expect($inspected)->toBe(['Content-Signal' => 'ai-train=no'])
        ->and($robots)->toBeInstanceOf(Response::class)
        ->and($robots->body())->toContain(implode("\n", [
            'User-agent: *',
            'Content-Signal: ai-train=yes',
            'Content-Signal: search=yes',
            'Disallow: /panel',
        ]));
});
