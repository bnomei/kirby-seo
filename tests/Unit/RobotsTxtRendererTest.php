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
