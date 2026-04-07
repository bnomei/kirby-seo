<?php

declare(strict_types=1);

use Bnomei\Seo\Kirby\Ai\SeoAiSourceExtractor;
use Bnomei\Seo\Kirby\Migration\MigrationCommandRegistry;
use Bnomei\Seo\Kirby\Panel\SeoPanelActions;
use Bnomei\Seo\Kirby\Panel\SeoPanelButtons;
use Bnomei\Seo\Kirby\SeoBlueprints;
use Bnomei\Seo\Kirby\SeoHooks;
use Bnomei\Seo\Kirby\SeoMethodRegistry;
use Bnomei\Seo\Kirby\SeoRoutes;
use Kirby\Cms\App as Kirby;

$readEnv = static function (string $key): string {
    if (function_exists('env') === true) {
        try {
            $value = env($key);

            if (is_string($value) === true) {
                return $value;
            }
        } catch (Throwable) {
            // Fall back to raw PHP env access when a global env helper exists
            // but its optional dependencies are not installed.
        }
    }

    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    return is_string($value) ? $value : '';
};

Kirby::plugin('bnomei/seo', [
    'icons' => [],
    'blueprints' => SeoBlueprints::extensions(),
    'commands' => MigrationCommandRegistry::commands(),
    'options' => [
        'enabled' => true,
        'cache.sitemap' => true,
        'sitemap.cache.minutes' => 7 * 24 * 60,
        'ai.source' => static function (
            \Kirby\Cms\Page|\Kirby\Cms\Site $model,
            string $languageCode,
            Kirby $kirby,
        ): string {
            unset($kirby);

            return (new SeoAiSourceExtractor())->extract($model, $languageCode);
        },
        'ai.providers.openai' => static function (Kirby $kirby) use ($readEnv): array {
            unset($kirby);

            return [
                'apiKey' => $readEnv('OPENAI_API_KEY'),
            ];
        },
        'ai.providers.anthropic' => static function (Kirby $kirby) use ($readEnv): array {
            unset($kirby);

            return [
                'apiKey' => $readEnv('ANTHROPIC_API_KEY'),
            ];
        },
        'ai.providers.gemini' => static function (Kirby $kirby) use ($readEnv): array {
            unset($kirby);

            return [
                'apiKey' => $readEnv('GEMINI_API_KEY'),
            ];
        },
    ],
    'pageMethods' => SeoMethodRegistry::pageMethods(),
    'siteMethods' => SeoMethodRegistry::siteMethods(),
    'areas' => [
        'seo' => fn() => [
            'buttons' => [
                'seo' => fn($model) => $model instanceof \Kirby\Cms\Site
                    ? SeoPanelButtons::site($model)
                    : SeoPanelButtons::page($model),
            ],
            'dropdowns' => [
                'seo.page' => [
                    'pattern' => 'seo/page/(:all)',
                    'options' => fn(string $id) => SeoPanelActions::dropdown(\Kirby\Cms\Find::page($id)),
                ],
                'seo.site' => [
                    'pattern' => 'seo/site',
                    'options' => fn() => SeoPanelActions::dropdown(Kirby::instance()->site()),
                ],
            ],
            'views' => [
                'seo.page.action' => [
                    'pattern' => 'seo/page/(:all)/action/(:any)',
                    'action' => fn(string $id, string $action) => SeoPanelActions::perform(
                        \Kirby\Cms\Find::page($id),
                        $action,
                    ),
                ],
                'seo.site.action' => [
                    'pattern' => 'seo/site/action/(:any)',
                    'action' => fn(string $action) => SeoPanelActions::perform(Kirby::instance()->site(), $action),
                ],
            ],
        ],
    ],
    'snippets' => [
        'meta' => __DIR__ . '/snippets/seo/head.php',
        'seo/head' => __DIR__ . '/snippets/seo/head.php',
    ],
    'templates' => [],
    'translations' => [
        'en' => require __DIR__ . '/translations/en.php',
        'de' => require __DIR__ . '/translations/de.php',
    ],
    'hooks' => SeoHooks::pluginHooks(),
    'routes' => SeoRoutes::publicRoutes(),
]);
