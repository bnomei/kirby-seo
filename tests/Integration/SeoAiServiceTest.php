<?php

declare(strict_types=1);

use Bnomei\Seo\Kirby\Ai\SeoAiService;
use Bnomei\Seo\Kirby\SeoFields;

it('persists generated ai seo values into only the targeted translation', function (): void {
    $kirby = testKirby([
        'options' => [
            'bnomei.seo.ai.generate' => static function ($request) {
                return [
                    'title' => 'Generated ' . $request->languageCode,
                    'description' => 'Description ' . $request->languageCode,
                ];
            },
        ],
    ]);

    $service = SeoAiService::for($kirby);
    $page = $kirby->site()->find('about');
    $updated = $service->generatePage($page, 'en');
    $deContentFile = $updated->version('latest')->contentFile('de');
    $deContent = file_get_contents($deContentFile);

    expect($updated->content('en')->get('seoTitle')->value())->toBe('Generated en');
    expect($updated->content('en')->get('seoDescription')->value())->toBe('Description en');
    expect($updated->content('en')->get('seoAiLanguage')->value())->toBe('en');
    expect($deContent)->not->toContain('Seotitle:');
    expect($deContent)->not->toContain('Seodescription:');
});

it('auto refreshes stale pages after content updates but skips locked pages', function (): void {
    $calls = 0;
    $kirby = testKirby([
        'options' => [
            'bnomei.seo.ai.generate' => static function ($request) use (&$calls) {
                $calls++;

                return [
                    'title' => 'Hook ' . $request->languageCode,
                    'description' => 'Hook description ' . $request->languageCode,
                ];
            },
        ],
    ]);

    $page = $kirby->site()->find('about');
    $page = $kirby->impersonate('kirby', fn () => $page->update(['text' => 'Updated English body'], 'en', false));
    $page = $kirby->site()->find('about');

    expect($calls)->toBe(1);
    expect($page->content('en')->get('seoTitle')->value())->toBe('Hook en');

    $calls = 0;
    $page = SeoAiService::for($kirby)->setAiState($page, 'locked', 'en');
    $payload = ['text' => 'Updated again'];

    foreach (SeoFields::all() as $field) {
        $value = $page->content('en')->get($field)->value();

        if ($value !== null && $value !== '') {
            $payload[$field] = $value;
        }
    }

    $page = $kirby->impersonate('kirby', fn () => $page->update($payload, 'en', false));
    $page = $kirby->site()->find('about');

    expect($calls)->toBe(0);
    expect($page->content('en')->get('seoAiState')->value())->toBe('locked');
    expect($page->content('en')->get('seoTitle')->value())->toBe('Hook en');
});

it('builds ai source text from rendered page markdown by default', function (): void {
    $source = null;
    $kirby = testKirby([
        'options' => [
            'bnomei.seo.ai.generate' => static function ($request) use (&$source) {
                $source = $request->snapshot->text;

                return [
                    'title' => 'Generated ' . $request->languageCode,
                    'description' => 'Description ' . $request->languageCode,
                ];
            },
        ],
    ]);

    $page = $kirby->site()->find('about');

    SeoAiService::for($kirby)->generatePage($page, 'en');

    expect($source)->toBe("# About\n\nUpdated English body");
});

it('allows overriding the ai source text with a closure that returns a string', function (): void {
    $source = null;
    $kirby = testKirby([
        'options' => [
            'bnomei.seo.ai.source' => static function ($model, string $languageCode): string {
                return (string) $model->content($languageCode)->get('text')->value();
            },
            'bnomei.seo.ai.generate' => static function ($request) use (&$source) {
                $source = $request->snapshot->text;

                return [
                    'title' => 'Generated ' . $request->languageCode,
                    'description' => 'Description ' . $request->languageCode,
                ];
            },
        ],
    ]);

    $page = $kirby->site()->find('about');

    SeoAiService::for($kirby)->generatePage($page, 'en');

    expect($source)->toBe('Updated English body');
});

it('strips images and non-body noise from rendered ai source markdown', function (): void {
    $source = null;
    $kirby = testKirbyWithSandboxedSite([
        'options' => [
            'bnomei.seo.ai.generate' => static function ($request) use (&$source) {
                $source = $request->snapshot->text;

                return [
                    'title' => 'Generated ' . $request->languageCode,
                    'description' => 'Description ' . $request->languageCode,
                ];
            },
        ],
    ]);

    file_put_contents($kirby->root('site') . '/templates/default.php', <<<'PHP'
<!doctype html>
<html lang="<?= $kirby->language()?->code() ?? 'en' ?>">
<head>
    <meta charset="utf-8">
    <title>Head Noise</title>
</head>
<body>
<main>
    <h1><?= $page->title()->escape() ?></h1>
    <?= $page->text()->kirbytext() ?>
    <img src="/hero.jpg" alt="Hero image">
    <script>window.__seo_noise = true;</script>
</main>
</body>
</html>
PHP);

    $page = $kirby->site()->find('about');

    SeoAiService::for($kirby)->generatePage($page, 'en');

    expect($source)->toContain('# About');
    expect($source)->toContain('Updated English body');
    expect($source)->not->toContain('Head Noise');
    expect($source)->not->toContain('hero.jpg');
    expect($source)->not->toContain('Hero image');
    expect($source)->not->toContain('__seo_noise');
});

it('uses the rendered homepage as the default ai source for site generation', function (): void {
    $source = null;
    $kirby = testKirby([
        'options' => [
            'bnomei.seo.ai.generate' => static function ($request) use (&$source) {
                $source = $request->snapshot->text;

                return [
                    'title' => 'Generated ' . $request->languageCode,
                    'description' => 'Description ' . $request->languageCode,
                ];
            },
        ],
    ]);

    SeoAiService::for($kirby)->generateSite($kirby->site(), 'en');

    expect($source)->toBe("# Home\n\nWelcome home");
});

it('builds site ai source text from the rendered home page markdown by default', function (): void {
    $source = null;
    $kirby = testKirby([
        'options' => [
            'bnomei.seo.ai.generate' => static function ($request) use (&$source) {
                $source = $request->snapshot->text;

                return [
                    'title' => 'Generated ' . $request->languageCode,
                    'description' => 'Description ' . $request->languageCode,
                ];
            },
        ],
    ]);

    SeoAiService::for($kirby)->generateSite($kirby->site(), 'en');

    expect($source)->toBe("# Home\n\nWelcome home");
});
