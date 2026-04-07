<?php

declare(strict_types=1);

use Kirby\Panel\Plugins;

it('boots the embedded kirby fixture app', function (): void {
    $kirby = testKirby();

    expect($kirby->plugin('bnomei/seo'))->not->toBeNull();
    expect($kirby->plugin('johannschopplich/serp-preview'))->not->toBeNull();
    expect($kirby->site()->homePage()->title()->value())->toBe('Home');
    expect($kirby->site()->find('about'))->not->toBeNull();
});

it('registers the custom robot panel icon in the plugin bundle', function (): void {
    $kirby = testKirby();
    $plugin = $kirby->plugin('bnomei/seo');
    $plugins = new Plugins();

    expect($plugin)->not->toBeNull();
    expect($plugin->extends())->toHaveKey('icons');
    expect($plugins->read('js'))->toContain('window.panel.plugin("bnomei/seo"');
    expect($plugins->read('js'))->toContain('seo: `<svg');
    expect($plugins->read('js'))->toContain('robot: `<svg');
    expect($plugins->read('js'))->toContain('viewButtons');
    expect($plugins->read('js'))->toContain('extends: "k-view-button"');
    expect($plugins->read('css'))->toContain('--seo-view-button-color-icon-ok');
    expect($plugins->read('css'))->toContain('.k-seo-view-button--problem');
});

it('resolves default ai provider api keys from environment variables', function (): void {
    putenv('OPENAI_API_KEY=openai-test-key');
    putenv('ANTHROPIC_API_KEY=anthropic-test-key');
    putenv('GEMINI_API_KEY=gemini-test-key');

    try {
        $kirby = testKirby();
        $openAi = $kirby->option('bnomei.seo.ai.providers.openai');
        $anthropic = $kirby->option('bnomei.seo.ai.providers.anthropic');
        $gemini = $kirby->option('bnomei.seo.ai.providers.gemini');

        expect($openAi)->toBeInstanceOf(Closure::class);
        expect($anthropic)->toBeInstanceOf(Closure::class);
        expect($gemini)->toBeInstanceOf(Closure::class);
        expect($openAi($kirby))->toMatchArray(['apiKey' => 'openai-test-key']);
        expect($anthropic($kirby))->toMatchArray(['apiKey' => 'anthropic-test-key']);
        expect($gemini($kirby))->toMatchArray(['apiKey' => 'gemini-test-key']);
    } finally {
        putenv('OPENAI_API_KEY');
        putenv('ANTHROPIC_API_KEY');
        putenv('GEMINI_API_KEY');
    }
});

it('registers the default ai source extractor as a closure option', function (): void {
    $kirby = testKirby();
    $source = $kirby->option('bnomei.seo.ai.source');

    expect($source)->toBeInstanceOf(Closure::class);
});

it('registers sitemap cache defaults for the plugin', function (): void {
    $kirby = testKirby();
    $cache = $kirby->cache('bnomei.seo.sitemap');

    expect($kirby->option('bnomei.seo.cache.sitemap'))->toBeTrue();
    expect($kirby->option('bnomei.seo.sitemap.cache.minutes'))->toBe(7 * 24 * 60);
    expect($cache->enabled())->toBeTrue();
});
