<?php

declare(strict_types=1);

use Bnomei\Seo\Core\Renderer\SitemapXmlRenderer;
use Bnomei\Seo\Core\Value\SeoSitemapEntry;

it('renders sitemap xml entries with alternates and lastmod', function (): void {
    $xml = (new SitemapXmlRenderer())->render([
        new SeoSitemapEntry(
            loc: 'https://example.com/en/about',
            lastModifiedAt: new DateTimeImmutable('2026-04-01T10:00:00+00:00'),
            alternates: [
                'de' => 'https://example.com/de/ueber-uns',
                'en' => 'https://example.com/en/about',
            ],
        ),
        new SeoSitemapEntry(
            loc: 'https://example.com/en',
            lastModifiedAt: new DateTimeImmutable('2026-04-02T12:00:00+00:00'),
            alternates: [
                'de' => 'https://example.com/de',
                'en' => 'https://example.com/en',
            ],
        ),
    ]);

    expect($xml)->normalized()->toMatchSnapshot();
});
