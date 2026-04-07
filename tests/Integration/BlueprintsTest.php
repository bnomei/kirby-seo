<?php

declare(strict_types=1);

it('keeps serp preview in the content tab without a dedicated seo tab', function (): void {
    $kirby = testKirby();

    $pageBlueprint = $kirby->site()->homePage()->blueprint();
    $siteBlueprint = $kirby->site()->blueprint();

    expect($pageBlueprint->tab('seo'))->toBeNull();
    expect($siteBlueprint->tab('seo'))->toBeNull();
    expect($pageBlueprint->section('serpPreview'))->not->toBeNull();
    expect($siteBlueprint->section('serpPreview'))->not->toBeNull();

    $pageContentTab = $pageBlueprint->tab('content');
    $siteContentTab = $siteBlueprint->tab('content');
    $pageSerpPreview = $pageContentTab['columns']['sidebar']['sections']['serpPreview'];
    $siteSerpPreview = $siteContentTab['columns']['sidebar']['sections']['serpPreview'];

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
