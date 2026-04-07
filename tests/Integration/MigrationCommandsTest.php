<?php

declare(strict_types=1);

use Bnomei\Seo\Kirby\Migration\MigrationReport;
use Bnomei\Seo\Kirby\SeoOptionReader;
use Kirby\Cms\Blueprint;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;

final class FakeMigrationCli
{
    /**
     * @var string[]
     */
    public array $lines = [];

    public function __construct(
        private readonly bool $apply = false,
    ) {}

    public function arg(string $name): bool
    {
        return $name === 'apply' ? $this->apply : false;
    }

    public function line(string $line): void
    {
        $this->lines[] = $line;
    }

    public function warning(string $line): void
    {
        $this->lines[] = $line;
    }
}

function persistLegacyModel(Page|Site $model, string $languageCode, array $values): Page|Site
{
    $kirby = $model->kirby();

    /** @var Page|Site $updated */
    $updated = $kirby->impersonate('kirby', function () use ($model, $languageCode, $values) {
        $payload = array_replace($model->content($languageCode)->toArray(), $values);

        return $model->update($payload, $languageCode, false);
    });

    return $updated;
}

function runMigrationCommand(\Kirby\Cms\App $kirby, string $name, bool $apply = false): array
{
    $commands = $kirby->extensions('commands');
    $cli = new FakeMigrationCli($apply);

    /** @var MigrationReport $report */
    $report = $commands[$name]['command']($cli);

    return [$report, $cli];
}

it('registers migration commands and keeps compatibility aliases working', function (): void {
    $kirby = testKirby([
        'options' => [
            'tobimori.seo.sitemap.enabled' => false,
            'fabianmichael.meta.sitemap.templates.exclude' => ['default'],
        ],
    ]);

    $commands = $kirby->extensions('commands');

    expect(array_keys($commands))->toContain('bnomei-seo:migrate-tobimori-v1');
    expect(array_keys($commands))->toContain('bnomei-seo:migrate-tobimori-v2');
    expect(array_keys($commands))->toContain('bnomei-seo:migrate-fabianmeta');

    expect(Blueprint::load('seo/page'))->toHaveKey('label');
    expect(Blueprint::load('tabs/meta/site'))->toHaveKey('label');

    $page = persistLegacyModel($kirby->site()->homePage(), 'en', [
        'metaTitle' => 'Legacy title',
        'metaDescription' => 'Legacy description',
        'meta_canonical_url' => 'https://example.com/home',
        'ogImage' => 'https://example.com/legacy-image.jpg',
        'robots' => 'noindex,nofollow',
    ]);

    expect($page->seoPreviewTitle())->toBe('Legacy title');
    expect($page->seoPreviewDescription())->toBe('Legacy description');
    expect($page->seoCanonical())->toBe('https://example.com/home');
    expect($page->seoResolvedDocument()->meta->imageUrl)->toBe('https://example.com/legacy-image.jpg');
    expect($page->seoIndexStatus())->toBe('Blocked');

    $page = persistLegacyModel($page, 'en', [
        'seoTitle' => 'Native title',
        'metaTitle' => 'Legacy title',
    ]);

    expect($page->seoPreviewTitle())->toBe('Native title');
    expect(snippet('meta', ['page' => $page], true))->toContain('<title>');

    $options = new SeoOptionReader($kirby);

    expect($options->sitemapActive())->toBeFalse();
    expect($options->sitemapIgnoreTemplates())->toBe(['default']);
});

it('plans Tobimori v1 migrations by default and applies multilingual content plus blueprint rewrites on demand', function (): void {
    $kirby = testKirbyWithSandboxedSite();
    $page = $kirby->site()->homePage();
    $site = $kirby->site();
    $blueprint = $kirby->root('blueprints') . '/pages/legacy-tobimori.yml';

    F::write($blueprint, <<<YML
title: Legacy
tabs:
  seo:
    extends: seo/page
YML);

    $page = persistLegacyModel($page, 'en', [
        'metaTitle' => 'Old home en',
        'metaDescription' => 'Old desc en',
        'ogImage' => 'legacy-en.jpg',
        'robots' => 'noindex,nofollow',
        'twitterCardType' => 'summary_large_image',
    ]);
    $page = persistLegacyModel($page, 'de', [
        'metaTitle' => 'Alt home de',
        'metaDescription' => 'Alt desc de',
        'ogImage' => 'legacy-de.jpg',
        'robots' => 'index,follow',
    ]);
    $site = persistLegacyModel($site, 'en', [
        'metaTitle' => 'Old site en',
        'metaDescription' => 'Old site desc en',
    ]);
    $site = persistLegacyModel($site, 'de', [
        'metaTitle' => 'Alt site de',
        'metaDescription' => 'Alt site desc de',
    ]);

    [$dryRun, $dryCli] = runMigrationCommand($kirby, 'bnomei-seo:migrate-tobimori-v1');

    expect($dryRun->applied)->toBeFalse();
    expect($dryRun->modelChanges)->not->toBeEmpty();
    expect($dryRun->fileChanges)->toHaveCount(1);
    expect($dryRun->warnings)->toContain(
        'Tobimori SEO 1.1.2 page `home` [en]: unsupported field `twitterCardType` requires manual follow-up.',
    );
    expect(implode("\n", $dryCli->lines))->toContain('mode: dry-run');
    expect(file_get_contents($page->version('latest')->contentFile('en')))->not->toContain('Seocanonical:');
    expect(file_get_contents($blueprint))->toContain('extends: seo/page');

    [$applied] = runMigrationCommand($kirby, 'bnomei-seo:migrate-tobimori-v1', true);

    expect($applied->applied)->toBeTrue();
    expect(file_get_contents($page->version('latest')->contentFile('en')))->toContain('Seotitle: Old home en');
    expect(file_get_contents($page->version('latest')->contentFile('en')))->toContain('Seodescription: Old desc en');
    expect(file_get_contents($page->version('latest')->contentFile('en')))->toContain('Seoimage: legacy-en.jpg');
    expect(file_get_contents($page->version('latest')->contentFile('en')))->toContain('Seoindex: 0');
    expect(file_get_contents($page->version('latest')->contentFile('de')))->toContain('Seoindex: 1');
    expect(file_get_contents($site->version('latest')->contentFile('en')))->toContain('Seotitle: Old site en');
    expect(file_get_contents($blueprint))->toContain('extends: tabs/seo/page');
});

it('applies the distinct Tobimori v2 adapter and reports cropOgImage instead of twitter fields', function (): void {
    $kirby = testKirbyWithSandboxedSite([
        'options' => [
            'tobimori.seo.canonical.base' => 'https://example.com',
        ],
    ]);
    $page = $kirby->site()->homePage();

    $page = persistLegacyModel($page, 'en', [
        'metaTitle' => 'V2 home en',
        'metaDescription' => 'V2 desc en',
        'ogImage' => 'v2-en.jpg',
        'cropOgImage' => '1',
        'robots' => 'index,follow',
    ]);

    [$report] = runMigrationCommand($kirby, 'bnomei-seo:migrate-tobimori-v2', true);

    expect($report->warnings)->toContain(
        'Tobimori SEO current main / v2 page `home` [en]: unsupported field `cropOgImage` requires manual follow-up.',
    );
    expect($report->followUps)->toContain(
        'Legacy option `tobimori.seo.canonical.base` requires manual mapping into `bnomei.seo.*`.',
    );
    expect(file_get_contents($page->version('latest')->contentFile('en')))->toContain('Seotitle: V2 home en');
    expect(file_get_contents($page->version('latest')->contentFile('en')))->toContain('Seoindex: 1');
});

it('applies Fabian Meta migrations with snippet and blueprint rewrites', function (): void {
    $kirby = testKirbyWithSandboxedSite();
    $page = $kirby->site()->homePage();
    $site = $kirby->site();
    $blueprint = $kirby->root('blueprints') . '/pages/legacy-fabian.yml';
    $template = $kirby->root('site') . '/templates/legacy-fabian.php';

    F::write($blueprint, <<<YML
title: Legacy
tabs:
  seo:
    extends: tabs/meta/page
YML);
    F::write($template, "<?php snippet('meta', ['page' => \$page]) ?>\n");

    $page = persistLegacyModel($page, 'en', [
        'meta_title' => 'Fabian home en',
        'meta_description' => 'Fabian desc en',
        'meta_canonical_url' => 'https://example.com/fabian-home',
        'og_image' => 'https://example.com/fabian-home.jpg',
        'robots_index' => 'false',
        'robots_follow' => 'false',
    ]);
    $site = persistLegacyModel($site, 'en', [
        'meta_title' => 'Fabian site en',
        'meta_description' => 'Fabian site desc en',
    ]);

    [$report] = runMigrationCommand($kirby, 'bnomei-seo:migrate-fabianmeta', true);

    expect($report->fileChanges)->toHaveCount(2);
    expect($report->warnings)->toContain(
        'Fabian Michael Meta page `home` [en]: unsupported field `robots_follow` requires manual follow-up.',
    );
    expect(file_get_contents($page->version('latest')->contentFile('en')))->toContain('Seotitle: Fabian home en');
    expect(file_get_contents($page->version('latest')->contentFile('en')))->toContain('Seocanonical: https://example.com/fabian-home');
    expect(file_get_contents($page->version('latest')->contentFile('en')))->toContain('Seoimage: https://example.com/fabian-home.jpg');
    expect(file_get_contents($page->version('latest')->contentFile('en')))->toContain('Seoindex: 0');
    expect(file_get_contents($site->version('latest')->contentFile('en')))->toContain('Seotitle: Fabian site en');
    expect(file_get_contents($blueprint))->toContain('extends: tabs/seo/page');
    expect(file_get_contents($template))->toContain("snippet('seo/head'");
});
