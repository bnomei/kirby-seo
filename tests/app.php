<?php

declare(strict_types=1);

use Bnomei\Seo\Core\Ai\SeoAiRequest;
use Kirby\Cms\App;
use Kirby\Filesystem\Dir;

const KIRBY_HELPER_DUMP = false;
const KIRBY_HELPER_E = false;

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/index.php';

App::$enableWhoops = false;

$root = dirname(__DIR__);
$fixture = __DIR__ . '/site';
$runtime = $root . '/tests/runtime';
$public = __DIR__ . '/public';

Dir::make($runtime, true);
Dir::make($public . '/media', true);

if (is_dir($runtime . '/content') === false) {
    Dir::copy($fixture . '/content', $runtime . '/content');
}

foreach (['cache', 'sessions', 'accounts'] as $directory) {
    Dir::make($runtime . '/' . $directory, true);
}

return new App([
    'roots' => [
        'index' => $public,
        'base' => $root,
        'kirby' => $root . '/tests/kirby',
        'site' => $fixture,
        'content' => $runtime . '/content',
        'media' => $public . '/media',
        'cache' => $runtime . '/cache',
        'sessions' => $runtime . '/sessions',
        'accounts' => $runtime . '/accounts',
        'blueprints' => $fixture . '/blueprints',
        'languages' => $fixture . '/languages',
    ],
    'options' => [
        'debug' => true,
        // Exercise the complete Panel write path without sending fixture content to a provider.
        'bnomei.seo.ai.generate' => getenv('SEO_TEST_GENERATOR') !== '1' ? null : static fn (SeoAiRequest $request): array => [
            'title' => $request->snapshot->title . ' | SEO preview (' . $request->languageCode . ')',
            'description' => $request->snapshot->excerpt ?: $request->snapshot->title,
        ],
    ],
]);
