<?php

declare(strict_types=1);

use Bnomei\Seo\Kirby\SeoOptionReader;

it('registers seo page and site methods with meaningful defaults', function (): void {
    $kirby = testKirby();
    $page = $kirby->site()->homePage();
    $site = $kirby->site();
    $about = $kirby->site()->find('about');

    expect($page->seoPreviewTitle())->toBe('Home');
    expect($page->seoPreviewDescription())->toContain('home');
    expect($page->seoCanonical())->toBe($page->url());
    expect($page->seoIndexStatus())->toBe('Allowed');
    expect($page->seoSitemapStatus())->toBe('Excluded');

    expect($about)->not->toBeNull();
    expect($about?->seoResolvedDocument()->meta->imageUrl)->not->toBeNull();
    expect($about?->seoResolvedDocument()->meta->imageUrl)->toBe($about?->file('seo-image.jpg')?->thumb('seo')->url());
    expect($about?->seoResolvedDocument()->meta->imageUrl)->not->toBe($about?->file('seo-image.jpg')?->url());

    expect($site->seoPreviewTitle())->toBe('Fixture Site');
    expect($site->seoCanonical())->toBe($site->url());
    expect($site->seoLanguageLabel())->toContain('en');
});

it('supports legacy content and option fallbacks', function (): void {
    $kirby = testKirby([
        'options' => [
            'tobimori.seo.sitemap.enabled' => false,
        ],
    ]);

    $page = $kirby->site()->homePage();

    $page = $kirby->impersonate('kirby', function () use ($page) {
        $payload = array_replace($page->content('en')->toArray(), [
            'metaTitle' => 'Legacy title',
            'metaDescription' => 'Legacy description',
        ]);

        return $page->update($payload, 'en', false);
    });

    $options = new SeoOptionReader($kirby);

    expect($page->seoPreviewTitle())->toBe('Legacy title');
    expect($page->seoPreviewDescription())->toBe('Legacy description');
    expect($options->sitemapActive())->toBeFalse();
});
