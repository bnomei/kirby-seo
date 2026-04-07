<?php

declare(strict_types=1);

use Bnomei\Seo\Core\Value\SeoContext;
use Bnomei\Seo\Core\Value\SeoDocumentType;
use Bnomei\Seo\Core\Value\SeoVisibility;

it('serializes seo context values deterministically', function (): void {
    $context = new SeoContext(
        documentType: SeoDocumentType::Page,
        id: 'home',
        url: 'https://example.com/en',
        language: 'en',
        languageName: 'English',
        siteUrl: 'https://example.com',
        template: 'default',
        visibility: SeoVisibility::Listed,
        isHomepage: true,
        isMultilingual: true,
    );

    expect($context->toArray())->toBe([
        'documentType' => 'page',
        'id' => 'home',
        'url' => 'https://example.com/en',
        'language' => 'en',
        'languageName' => 'English',
        'siteUrl' => 'https://example.com',
        'template' => 'default',
        'visibility' => 'listed',
        'isHomepage' => true,
        'isMultilingual' => true,
    ]);
});
