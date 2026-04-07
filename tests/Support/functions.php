<?php

declare(strict_types=1);

use Kirby\Cms\App;
use Kirby\Filesystem\Dir;

function testKirby(array $overrides = []): App
{
    $root = dirname(__DIR__, 2);
    $fixture = __DIR__ . '/../site';
    $sandbox = sys_get_temp_dir() . '/kirby-seo-tests/' . bin2hex(random_bytes(8));

    Dir::make($sandbox, true);
    Dir::copy($fixture . '/content', $sandbox . '/content');
    Dir::make($sandbox . '/media', true);
    Dir::make($sandbox . '/cache', true);
    Dir::make($sandbox . '/sessions', true);
    Dir::make($sandbox . '/accounts', true);

    $options = [
        'roots' => [
            'index' => $root,
            'base' => $root,
            'kirby' => $root . '/tests/kirby',
            'site' => $fixture,
            'content' => $sandbox . '/content',
            'media' => $sandbox . '/media',
            'cache' => $sandbox . '/cache',
            'sessions' => $sandbox . '/sessions',
            'accounts' => $sandbox . '/accounts',
            'blueprints' => $fixture . '/blueprints',
            'languages' => $fixture . '/languages',
        ],
        'options' => [
            'debug' => true,
        ],
    ];

    return new App(array_replace_recursive($options, $overrides));
}

function testKirbyWithSandboxedSite(array $overrides = []): App
{
    $root = dirname(__DIR__, 2);
    $fixture = __DIR__ . '/../site';
    $sandbox = sys_get_temp_dir() . '/kirby-seo-tests/' . bin2hex(random_bytes(8));
    $siteRoot = $sandbox . '/site';

    Dir::make($sandbox, true);
    Dir::copy($fixture, $siteRoot);
    Dir::make($sandbox . '/plugins', true);
    Dir::make($sandbox . '/media', true);
    Dir::make($sandbox . '/cache', true);
    Dir::make($sandbox . '/sessions', true);
    Dir::make($sandbox . '/accounts', true);

    $options = [
        'roots' => [
            'index' => $root,
            'base' => $root,
            'kirby' => $root . '/tests/kirby',
            'site' => $siteRoot,
            'content' => $siteRoot . '/content',
            'media' => $sandbox . '/media',
            'cache' => $sandbox . '/cache',
            'sessions' => $sandbox . '/sessions',
            'accounts' => $sandbox . '/accounts',
            'plugins' => $sandbox . '/plugins',
            'blueprints' => $siteRoot . '/blueprints',
            'languages' => $siteRoot . '/languages',
        ],
        'options' => [
            'debug' => true,
        ],
    ];

    return new App(array_replace_recursive($options, $overrides));
}
