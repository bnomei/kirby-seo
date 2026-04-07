<?php

declare(strict_types=1);

use Bnomei\Seo\Core\Service\SeoResolver;
use Bnomei\Seo\Core\Value\SeoContentSnapshot;
use Bnomei\Seo\Core\Value\SeoContext;
use Bnomei\Seo\Core\Value\SeoDocumentType;
use Bnomei\Seo\Core\Value\SeoIndexabilityReason;
use Bnomei\Seo\Core\Value\SeoRuleSet;
use Bnomei\Seo\Core\Value\SeoSitemapReason;
use Bnomei\Seo\Core\Value\SeoVisibility;

it('resolves multilingual listed pages with alternates and title suffixes', function (): void {
    $resolver = new SeoResolver();

    $resolved = $resolver->resolve(
        context: new SeoContext(
            documentType: SeoDocumentType::Page,
            id: 'about',
            url: 'https://example.com/en/about',
            language: 'en',
            languageName: 'English',
            siteUrl: 'https://example.com',
            template: 'default',
            visibility: SeoVisibility::Listed,
            isMultilingual: true,
        ),
        snapshot: new SeoContentSnapshot(
            title: 'About',
            seoTitle: 'About Us',
            description: 'About page',
            excerpt: 'About excerpt',
            imageUrl: 'https://example.com/media/about.jpg',
            translationUrls: [
                'de' => 'https://example.com/de/ueber-uns',
                'en' => 'https://example.com/en/about',
            ],
            modifiedAt: new DateTimeImmutable('2026-04-01T10:00:00+00:00'),
        ),
        rules: new SeoRuleSet(
            titleSuffix: 'Example',
            defaultDescription: 'Fallback description',
        ),
    );

    expect($resolved->meta->title)->toBe('About Us | Example');
    expect($resolved->meta->canonicalUrl)->toBe('https://example.com/en/about');
    expect($resolved->meta->alternates)->toBe([
        'de' => 'https://example.com/de/ueber-uns',
        'en' => 'https://example.com/en/about',
    ]);
    expect($resolved->state->indexable)->toBeTrue();
    expect($resolved->state->sitemapIncluded)->toBeTrue();
    expect($resolved->sitemap->entry?->loc)->toBe('https://example.com/en/about');
});

it('blocks drafts from indexing and the sitemap', function (): void {
    $resolved = (new SeoResolver())->resolve(
        context: new SeoContext(
            documentType: SeoDocumentType::Page,
            id: 'draft-page',
            url: 'https://example.com/en/draft-page',
            language: 'en',
            visibility: SeoVisibility::Draft,
        ),
        snapshot: new SeoContentSnapshot(
            title: 'Draft Page',
        ),
    );

    expect($resolved->state->indexable)->toBeFalse();
    expect($resolved->state->indexabilityReason)->toBe(SeoIndexabilityReason::Draft);
    expect($resolved->state->sitemapIncluded)->toBeFalse();
    expect($resolved->state->sitemapReason)->toBe(SeoSitemapReason::Draft);
});

it('respects manual search overrides before config defaults', function (): void {
    $resolved = (new SeoResolver())->resolve(
        context: new SeoContext(
            documentType: SeoDocumentType::Page,
            id: 'manual-hidden',
            url: 'https://example.com/en/manual-hidden',
            language: 'en',
        ),
        snapshot: new SeoContentSnapshot(
            title: 'Manual Hidden',
            searchAllowed: false,
        ),
        rules: new SeoRuleSet(
            indexable: true,
        ),
    );

    expect($resolved->state->indexable)->toBeFalse();
    expect($resolved->state->indexabilityReason)->toBe(SeoIndexabilityReason::ManualBlocked);
    expect($resolved->meta->robots())->toBe('noindex, nofollow');
    expect($resolved->state->sitemapReason)->toBe(SeoSitemapReason::IndexingBlocked);
});

it('excludes unlisted pages from the sitemap by default visibility rules', function (): void {
    $resolved = (new SeoResolver())->resolve(
        context: new SeoContext(
            documentType: SeoDocumentType::Page,
            id: 'secret',
            url: 'https://example.com/en/secret',
            language: 'en',
            visibility: SeoVisibility::Unlisted,
        ),
        snapshot: new SeoContentSnapshot(
            title: 'Secret',
        ),
    );

    expect($resolved->state->indexable)->toBeTrue();
    expect($resolved->state->sitemapIncluded)->toBeFalse();
    expect($resolved->state->sitemapReason)->toBe(SeoSitemapReason::VisibilityExcluded);
});

it('requires a canonical url for sitemap inclusion when configured', function (): void {
    $resolved = (new SeoResolver())->resolve(
        context: new SeoContext(
            documentType: SeoDocumentType::Page,
            id: 'canonical-less',
            url: '',
            language: 'en',
        ),
        snapshot: new SeoContentSnapshot(
            title: 'Canonical Less',
            canonicalUrl: '',
        ),
        rules: new SeoRuleSet(
            sitemapRequireCanonical: true,
        ),
    );

    expect($resolved->meta->canonicalUrl)->toBeNull();
    expect($resolved->state->sitemapIncluded)->toBeFalse();
    expect($resolved->state->sitemapReason)->toBe(SeoSitemapReason::MissingCanonical);
});
