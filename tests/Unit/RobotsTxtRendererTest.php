<?php

declare(strict_types=1);

use Bnomei\Seo\Core\Renderer\RobotsTxtRenderer;
use Bnomei\Seo\Core\Value\SeoRobotsRuleGroup;
use Bnomei\Seo\Core\Value\SeoRobotsRules;

it('renders robots txt groups and sitemap lines deterministically', function (): void {
    $robots = new SeoRobotsRules(
        groups: [
            new SeoRobotsRuleGroup(
                userAgent: '*',
                allow: ['/'],
                disallow: ['/panel', '/drafts'],
                comments: ['Primary crawl policy']
            ),
        ],
        sitemapUrls: [
            'https://example.com/sitemap.xml',
        ],
    );

    $txt = (new RobotsTxtRenderer())->render($robots);

    expect($txt)->normalized()->toMatchSnapshot();
});

it('renders structured directives before allow and disallow rules', function (): void {
    $group = new SeoRobotsRuleGroup(
        userAgent: '*',
        allow: ['/media'],
        disallow: ['/kirby'],
        directives: [
            'Content-Signal' => 'ai-train=no, search=yes, ai-input=yes',
            'Clean-param' => ['ref /articles', 'sort /catalog'],
        ],
    );

    $txt = (new RobotsTxtRenderer())->render(new SeoRobotsRules(
        groups: [$group],
        sitemapUrls: ['https://example.com/sitemap.xml'],
    ));

    expect($txt)->toBe(implode("\n", [
        'User-agent: *',
        'Content-Signal: ai-train=no, search=yes, ai-input=yes',
        'Clean-param: ref /articles',
        'Clean-param: sort /catalog',
        'Allow: /media',
        'Disallow: /kirby',
        '',
        'Sitemap: https://example.com/sitemap.xml',
        '',
    ]));
});

it('omits empty structured directives', function (): void {
    $group = new SeoRobotsRuleGroup(userAgent: '*', directives: ['Content-Signal' => []]);

    expect((new RobotsTxtRenderer())->render(new SeoRobotsRules(groups: [$group])))
        ->toBe("User-agent: *\n");
});

it('normalizes and serializes structured directives', function (): void {
    $group = new SeoRobotsRuleGroup(
        userAgent: '*',
        directives: [
            "Content-Signal\r\nDisallow" => "ai-train=no\r\nDisallow: /private",
            'X-Policy' => ['one', "two\nSitemap: https://evil.example"],
        ],
    );

    expect($group->directives)->toBe([
        'Content-Signal Disallow' => ['ai-train=no Disallow: /private'],
        'X-Policy' => ['one', 'two Sitemap: https://evil.example'],
    ])->and($group->toArray()['directives'])->toBe($group->directives)
        ->and($group->jsonSerialize()['directives'])->toBe($group->directives)
        ->and(json_encode($group))->toContain('Content-Signal Disallow')
        ->and((new RobotsTxtRenderer())->render(new SeoRobotsRules(groups: [$group])))
        ->not->toContain("\r")
        ->not->toContain("\nDisallow: /private")
        ->not->toContain("\nSitemap: https://evil.example");
});
