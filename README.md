# Kirby SEO with AI

[![Kirby 6](https://flat.badgen.net/badge/Kirby/6?color=ECC748)](https://getkirby.com/releases/6)
![PHP 8.3+](https://flat.badgen.net/badge/PHP/8.3+?color=4E5B93&icon=php&label)
![Release](https://flat.badgen.net/packagist/v/bnomei/kirby-seo?color=ae81ff&icon=github&label)
[![Discord](https://flat.badgen.net/badge/discord/bnomei?color=7289da&icon=discord&label)](https://discordapp.com/users/bnomei)
[![Buymecoffee](https://flat.badgen.net/badge/icon/donate?icon=buymeacoffee&color=FF813F&label)](https://www.buymeacoffee.com/bnomei)

Minimal, mostly zero-config SEO plugin for Kirby CMS.

It gives you:

- resolved `<head>` metadata via a snippet or page/site methods
- automatic `robots.txt` and `sitemap.xml` routes
- optional Panel SEO button with status and AI actions
- optional SERP preview integration
- optional AI title/description generation

## Requirements

- Kirby 6
- PHP 8.3 or newer, within Kirby’s supported PHP versions

## Installation

```bash
composer require bnomei/kirby-seo
```

If you also want the Panel SERP preview field:

```bash
composer require johannschopplich/kirby-serp-preview:^2
```

## Zero Config

For the public SEO output, the plugin is basically zero config.

Add the snippet to your page template:

```php
<?php snippet('seo/head') ?>
```

The snippet renders the resolved head tags from the current page or site. See `snippets/seo/head.php`.

The plugin also registers public routes automatically:

- `robots.txt`
- `sitemap.xml`

See `src/Kirby/SeoRoutes.php`.

### Additional robots.txt directives

Use the `Directives` map in a robots group for directives beyond `Allow` and `Disallow`. Each
directive accepts one string or a list of strings, so repeated directives are supported without
special-casing their names. For example, to publish Content Signals:

```php
return [
    'bnomei.seo.robots.txt.groups' => [
        '*' => [
            'Directives' => [
                'Content-Signal' => [
                    'ai-train=no, search=yes, ai-input=yes',
                ],
            ],
            'Allow' => ['/media'],
            'Disallow' => ['/kirby', '/panel', '/content'],
        ],
    ],
];
```

Additional directives render after `User-agent` and before `Allow` and `Disallow`. The
`bnomei.seo.robots.txt.filter` callback receives the configured groups, including `Directives`,
and can inspect or modify them before rendering.

## Methods

The plugin registers page and site methods like:

- `$page->seoHeadTags()`
- `$page->seoPreviewTitle()`
- `$page->seoPreviewDescription()`
- `$page->seoCanonical()`
- `$site->seoHeadTags()`
- `$site->seoRobotsTxt()`
- `$site->seoSitemapXml()`

See `src/Kirby/SeoMethodRegistry.php`.

## Optional Panel Button

The SEO view button is opt-in. Add `seo` to your blueprint buttons.

For pages:

```yml
buttons:
  - open
  - preview
  - "-"
  - settings
  - languages
  - status
  - seo
```

For the site blueprint:

```yml
buttons:
  - open
  - preview
  - languages
  - seo
```

See `tests/site/blueprints/pages/default.yml` and `tests/site/blueprints/site.yml`.

What the button does:

- opens an SEO dropdown
- shows current indexing and sitemap state
- lets you run AI generation
- lets you pause/resume AI auto-refresh
- lets you hide/allow page indexing overrides

See `src/Kirby/Panel/SeoPanelButtons.php` and `src/Kirby/Panel/SeoPanelActions.php`.

The button also hints state:

- green icon when indexing and sitemap are both OK
- red button background when the resolved SEO state has a problem

## Optional SERP Preview

When upgrading from Kirby 5, move the preview from `sections:` to `fields:` and change its `extends:` prefix from `sections/seo/` to `fields/seo/`. The content keys stay the same.

For pages:

```yml
fields:
  serpPreview:
    extends: fields/seo/serp-preview/page
```

For the site:

```yml
fields:
  serpPreview:
    extends: fields/seo/serp-preview/site
```

See `src/Kirby/SeoBlueprints.php`.

These blueprints configure the preview to read the SEO title and description fields.

## Blueprint Fields and Overrides

The plugin stores SEO values in normal Kirby content fields, which means you can show them in the Panel and override AI output with regular blueprint fields.

The core fields are:

- `seoTitle` using Kirby's `text` field
- `seoDescription` using Kirby's `textarea` field with `buttons: false`
- `seoCanonical` using Kirby's `url` field
- `seoImage` using Kirby's `filepicker` field
- `seoIndex` using Kirby's `radio` field with `Default`, `Allowed`, and `Blocked` options

`seoTitle` and `seoDescription` are the fields that AI writes to. If an editor changes either value in the Panel, that saved content becomes the resolved SEO output. If AI is still set to `auto`, other content changes can trigger a refresh and regenerate those values again, so use the SEO lock action if you want to keep a manual override. `seoCanonical`, `seoImage`, and `seoIndex` are regular manual overrides.

To keep your existing content tab, add the SEO fields in a sidebar column:

```yml
tabs:
  content:
    label: Content
    columns:
      main:
        width: 2/3
        fields:
          text:
            type: textarea
      sidebar:
        width: 1/3
        fields:
          serpPreview:
            extends: fields/seo/serp-preview/page
          seoTitle:
            type: text
            label: SEO title
          seoDescription:
            type: textarea
            label: SEO description
            buttons: false
            maxlength: 320
          seoCanonical:
            type: url
            label: Canonical URL
          seoImage:
            type: filepicker
            label: SEO image
            max: 1
            multiple: false
            uploads: false
            query: page.images
          seoIndex:
            type: radio
            label: Search indexing
            default: default
            columns: 3
            options:
              - value: default
                text: Default
              - value: allowed
                text: Allowed
              - value: blocked
                text: Blocked
```

For the site blueprint, change the preview field and image query to `fields/seo/serp-preview/site` and `site.images`.

Kirby's `textarea` field includes built-in format buttons by default, so `buttons: false` keeps the SEO description plain. If you also want other metadata in the same area, Kirby's `tags` field is available for keyword-style input, while the SEO indexing override should stay a three-option `radio` field so editors can explicitly keep the runtime default.

If you prefer the ready-made SEO tab instead of wiring the fields manually, extend `seo/page` or `seo/site` in your blueprint. The plugin also keeps `tabs/seo/page` and `tabs/seo/site` available for compatibility.

## AI

AI generation is optional.

Default provider behavior:

- provider: `openai`
- model: `gpt-5-mini`
- alternatives: `anthropic`, `gemini`

See `src/Kirby/Ai/SeoAiService.php`.

### Environment Variables

By default the plugin already looks for these environment variables in closures:

- `OPENAI_API_KEY`
- `ANTHROPIC_API_KEY`
- `GEMINI_API_KEY`

See `index.php`.

If your hosting injects those into PHP, you usually don’t need extra config.

### Using `bnomei/kirby3-dotenv`

If you load secrets from `.env` files with `bnomei/kirby3-dotenv`, use the same callback pattern as in that plugin’s README: resolve secrets inside closures so they are evaluated after plugins are loaded.

Example:

```php
<?php

return [
    'bnomei.seo.ai.provider' => 'openai',
    'bnomei.seo.ai.providers.openai' => function () {
        return [
            'apiKey' => env('OPENAI_API_KEY'),
            'model' => 'gpt-5-mini',
            'timeout' => 30,
        ];
    },
];
```

You can do the same for Anthropic or Gemini:

```php
<?php

return [
    'bnomei.seo.ai.provider' => 'anthropic',
    'bnomei.seo.ai.providers.anthropic' => function () {
        return [
            'apiKey' => env('ANTHROPIC_API_KEY'),
            'model' => 'claude-sonnet-4-5',
        ];
    },
];
```

### Custom Connectors

If you do not want one of the built-in OpenAI, Anthropic, or Gemini providers, there are two extension points:

- `bnomei.seo.ai.generate` for a full custom implementation that returns the final SEO title and description
- `bnomei.seo.ai.transport` for replacing only the HTTP transport under the built-in adapters

Example:

```php
<?php

use Bnomei\Seo\Core\Ai\SeoAiHttpRequest;
use Bnomei\Seo\Core\Ai\SeoAiHttpResponse;

return [
    'bnomei.seo.ai.generate' => function ($request, $model, string $languageCode, $kirby) {
        unset($model, $languageCode, $kirby);

        // Call your own inference API here and normalize the response.
        return [
            'title' => 'Custom title',
            'description' => 'Custom description',
        ];
    },
    'bnomei.seo.ai.transport' => function (SeoAiHttpRequest $request) {
        unset($request);

        return new SeoAiHttpResponse(
            statusCode: 200,
            body: ['output_text' => '{"title":"Custom title","description":"Custom description"}'],
        );
    },
];
```

If you want a fourth named provider, add a new adapter class under `src/Kirby/Ai` and extend `SeoAiService::adapter()` accordingly. The built-in selector only maps `openai`, `anthropic`, and `gemini`.

## AI Source

The AI input is not the raw `text` field by default.

The default source pipeline:

- renders the page HTML
- prefers `<main>` and otherwise falls back to `<body>`
- strips images and other non-markdown-ish nodes
- converts the remaining HTML to markdown

For site-wide generation it uses the rendered home page. See `src/Kirby/Ai/SeoAiSnapshotFactory.php`, `src/Kirby/Ai/SeoAiSourceExtractor.php`, and `src/Kirby/Ai/SeoAiHtmlToMarkdownConverter.php`.

If you want to override that, return any string from `bnomei.seo.ai.source`:

```php
<?php

return [
    'bnomei.seo.ai.source' => function ($model, string $languageCode) {
        return (string) $model->content($languageCode)->get('text')->value();
    },
];
```

That gives you full control over what the LLM sees.

## Test the Panel in an Amp orb

Run `amp orb services ensure` and open the returned Panel link. Sign in as `editor@example.test` with the generated password in `tests/runtime/panel-password`. The test site edits a disposable copy of the bilingual fixture. Generate uses a local callback; no provider key is needed.

Run `composer test` for the PHP suite and `bash tests/bin/panel-smoke.sh` for an authenticated browser check. The latter hides the Listed page, reloads it, verifies its public robots tag and sitemap entry, then allows it again.
