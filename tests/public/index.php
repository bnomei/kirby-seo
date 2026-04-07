<?php

declare(strict_types=1);

use Kirby\Cms\App;
use Kirby\Filesystem\Dir;

const KIRBY_HELPER_DUMP = false;
const KIRBY_HELPER_E = false;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/index.php';

App::$enableWhoops = false;

$root = dirname(__DIR__, 2);
$fixture = dirname(__DIR__) . '/site';
$runtime = $root . '/tests/runtime';
$public = __DIR__;

Dir::make($runtime, true);
Dir::make($public . '/media', true);

if (is_dir($runtime . '/content') === false) {
    Dir::copy($fixture . '/content', $runtime . '/content');
}

foreach (['cache', 'sessions', 'accounts'] as $directory) {
    Dir::make($runtime . '/' . $directory, true);
}

$kirby = new App([
    'roots' => [
        'index' => __DIR__,
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
    ],
]);

echo $kirby->render();
