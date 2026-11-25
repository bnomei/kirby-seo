<?php

declare(strict_types=1);

it('keeps serp preview in the content tab without a dedicated seo tab', function (): void {
    $kirby = testKirby();

    $pageBlueprint = $kirby->site()->homePage()->blueprint();
    $siteBlueprint = $kirby->site()->blueprint();

    expect($pageBlueprint->tab('seo'))->toBeNull();
    expect($siteBlueprint->tab('seo'))->toBeNull();
    expect($pageBlueprint->tab('content')->fieldNames())->toContain('serppreview');
    expect($siteBlueprint->tab('content')->fieldNames())->toContain('serppreview');
    $pageSerpPreview = $pageBlueprint->field('serppreview');
    $siteSerpPreview = $siteBlueprint->field('serppreview');

    expect($pageSerpPreview['type'])->toBe('serp-preview');
    expect($pageSerpPreview['siteTitle'])->toBe('{{ site.title.value }}');
    expect($pageSerpPreview['siteUrl'])->toBe('{{ kirby.url }}');
    expect($pageSerpPreview['titleContentKey'])->toBe('seoTitle');
    expect($pageSerpPreview['defaultTitle'])->toBe('{{ page.title.value }} – {{ site.title.value }}');
    expect($pageSerpPreview['descriptionContentKey'])->toBe('seoDescription');
    expect($pageSerpPreview['defaultDescription'])->toBe('{{ page.text }}');

    expect($siteSerpPreview['type'])->toBe('serp-preview');
    expect($siteSerpPreview['siteTitle'])->toBe('{{ site.title.value }}');
    expect($siteSerpPreview['siteUrl'])->toBe('{{ kirby.url }}');
    expect($siteSerpPreview['titleContentKey'])->toBe('seoTitle');
    expect($siteSerpPreview['defaultTitle'])->toBe('{{ site.title.value }}');
    expect($siteSerpPreview['descriptionContentKey'])->toBe('seoDescription');
    expect($siteSerpPreview['defaultDescription'])->toBe('{{ site.description.value }}');
});

it('uses a tri-state radio field for the bundled search index override', function (): void {
    $seoPageTab = \Bnomei\Seo\Kirby\SeoBlueprints::extensions()['seo/page']();
    $seoIndexField = $seoPageTab['columns']['sidebar']['fields']['seoIndex'];

    expect($seoIndexField['type'])->toBe('radio');
    expect($seoIndexField['default'])->toBe('default');
    expect($seoIndexField['columns'])->toBe(3);
    expect($seoIndexField['options'])->toBe([
        [
            'value' => 'default',
            'text' => 'Default',
        ],
        [
            'value' => 'allowed',
            'text' => 'Allowed',
        ],
        [
            'value' => 'blocked',
            'text' => 'Blocked',
        ],
    ]);
});
