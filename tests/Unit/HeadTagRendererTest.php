<?php

declare(strict_types=1);

use Bnomei\Seo\Core\Renderer\HeadTagRenderer;
use Bnomei\Seo\Core\Service\SeoResolver;
use Bnomei\Seo\Core\Value\SeoContentSnapshot;
use Bnomei\Seo\Core\Value\SeoContext;
use Bnomei\Seo\Core\Value\SeoDocumentType;
use Bnomei\Seo\Core\Value\SeoRuleSet;

it('renders head tags from the resolved document payload', function (): void {
    $document = (new SeoResolver())->resolve(
        context: new SeoContext(
            documentType: SeoDocumentType::Page,
            id: 'about',
            url: 'https://example.com/en/about',
            language: 'en',
            isMultilingual: true,
        ),
        snapshot: new SeoContentSnapshot(
            title: 'About',
            seoTitle: 'About Us',
            seoDescription: 'Generated description',
            imageUrl: 'https://example.com/media/about.jpg',
            translationUrls: [
                'de' => 'https://example.com/de/ueber-uns',
                'en' => 'https://example.com/en/about',
            ],
        ),
        rules: new SeoRuleSet(
            titleSuffix: 'Example',
            metaTags: [
                'author' => 'Bnomei',
            ],
        ),
    );

    $html = (new HeadTagRenderer())->render($document);

    expect(substr_count($html, '<meta name="description"'))->toBe(1);
    expect($html)->normalized()->toMatchSnapshot();
});
