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

it('serves the SEO icons and styles as Panel plugin assets', function (): void {
    $kirby = testKirby();
    $plugin = $kirby->plugin('bnomei/seo');
    $plugins = new Plugins();

    expect($plugin)->not->toBeNull();
    expect($plugin->extends())->toHaveKey('icons');
    foreach (['js' => 'robot: `<svg', 'css' => '.k-seo-view-button--problem'] as $extension => $needle) {
        $hash = hash_file('xxh3', $plugin->root() . '/index.' . $extension);
        expect($plugins->{$extension}())->toContain($plugin->mediaUrl() . '.' . $hash . '.' . $extension);
        expect(Plugins::resolve('bnomei/seo', $hash, $extension)->body())->toContain($needle);
    }
});

it('resolves default ai provider api keys from environment variables', function (): void {
    $original = $_ENV;
    $_ENV['OPENAI_API_KEY'] = 'openai-test-key';
    $_ENV['ANTHROPIC_API_KEY'] = 'anthropic-test-key';
    $_ENV['GEMINI_API_KEY'] = 'gemini-test-key';

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
        $_ENV = $original;
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
