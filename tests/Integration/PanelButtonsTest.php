<?php

declare(strict_types=1);

use Bnomei\Seo\Kirby\Panel\SeoPanelActions;
use Bnomei\Seo\Kirby\Panel\SeoPanelButtons;
use Kirby\Panel\Redirect;

it('registers seo view buttons as a reusable panel area extension', function (): void {
    $kirby = testKirby();
    $areas = $kirby->extensions('areas');
    $page = $kirby->site()->homePage();
    $site = $kirby->site();

    expect($areas)->toHaveKey('seo');
    expect(SeoPanelButtons::page($page)['options'])->toBe('seo/page/home');
    expect(SeoPanelButtons::site($site)['options'])->toBe('seo/site');
});

it('keeps kirby default page and site buttons alongside seo in the fixture blueprints', function (): void {
    $kirby = testKirby();
    $pageButtons = array_column($kirby->site()->homePage()->panel()->buttons(), 'component');
    $siteButtons = array_column($kirby->site()->panel()->buttons(), 'component');

    expect($pageButtons)->toContain('k-open-view-button');
    expect($pageButtons)->toContain('k-preview-view-button');
    expect($pageButtons)->toContain('k-settings-view-button');
    expect($pageButtons)->toContain('k-languages-view-button');
    expect($pageButtons)->toContain('k-status-view-button');
    expect($pageButtons)->toContain('k-seo-view-button');

    expect($siteButtons)->toContain('k-open-view-button');
    expect($siteButtons)->toContain('k-preview-view-button');
    expect($siteButtons)->toContain('k-languages-view-button');
    expect($siteButtons)->toContain('k-seo-view-button');
});

it('styles seo view button definitions from the resolved seo state', function (): void {
    $kirby = testKirby();
    $siteButton = SeoPanelButtons::site($kirby->site());
    $pageButton = SeoPanelButtons::page($kirby->site()->find('about'));

    expect($siteButton['class'])->toBe('k-seo-view-button k-seo-view-button--ok');
    expect($siteButton['icon'])->toBe('seo');
    expect($siteButton['options'])->toBe('seo/site');
    expect($siteButton)->not->toHaveKey('theme');

    expect($pageButton['class'])->toBe('k-seo-view-button k-seo-view-button--problem');
    expect($pageButton['icon'])->toBe('seo');
    expect($pageButton['options'])->toBe('seo/page/about');
    expect($pageButton)->not->toHaveKey('theme');
});

it('exposes seo dropdown status rows and language-aware panel submit handlers', function (): void {
    $kirby = testKirby([
        'options' => [
            'panel.language' => 'de',
            'bnomei.seo.ai.generate' => static function ($request) {
                return [
                    'title' => 'Panel ' . $request->languageCode,
                    'description' => 'Panel description ' . $request->languageCode,
                ];
            },
        ],
    ]);

    $page = $kirby->site()->find('about');
    $kirby->setCurrentLanguage('de');
    $kirby->setCurrentTranslation('de');

    $dropdown = SeoPanelActions::dropdown($page);

    expect($dropdown)->toHaveKeys(['indexStatus', 'sitemapStatus', 'generate', 'unlock', 'lock', 'allow', 'hide']);
    expect(array_values(array_filter(array_keys($dropdown), 'is_string')))->toBe([
        'indexStatus',
        'sitemapStatus',
        'generate',
        'unlock',
        'lock',
        'allow',
        'hide',
    ]);
    expect($dropdown)->not->toHaveKey('refresh');
    expect($dropdown['indexStatus']['text'])->toBe('Suchindexierung: Erlaubt');
    expect($dropdown['sitemapStatus']['text'])->toBe('Sitemap: Ausgeschlossen');
    expect($dropdown['indexStatus']['class'])->toBe('k-seo-dropdown-status k-seo-dropdown-status--ok');
    expect($dropdown['sitemapStatus']['class'])->toBe('k-seo-dropdown-status k-seo-dropdown-status--problem');
    expect($dropdown['indexStatus']['disabled'])->toBeTrue();
    expect($dropdown['sitemapStatus']['disabled'])->toBeTrue();
    expect($dropdown['indexStatus']['icon'])->toBe('robot');
    expect($dropdown['sitemapStatus']['icon'])->toBe('sitemap');
    expect($dropdown['generate']['text'])->toBe('Generieren');
    expect($dropdown['lock']['text'])->toBe('AI-Auto-Aktualisierung pausieren');
    expect($dropdown['unlock']['text'])->toBe('AI-Auto-Aktualisierung fortsetzen');
    expect($dropdown['generate']['icon'])->toBe('ai');
    expect($dropdown['lock']['icon'])->toBe('lock');
    expect($dropdown['unlock']['icon'])->toBe('unlock');
    expect($dropdown['hide']['icon'])->toBe('hidden');
    expect($dropdown['allow']['icon'])->toBe('preview');
    expect($dropdown['generate']['link'])->toBe('seo/page/about/action/generate');
    expect($dropdown['lock']['link'])->toBe('seo/page/about/action/lock');
    expect($dropdown['unlock']['link'])->toBe('seo/page/about/action/unlock');
    expect($dropdown['lock']['disabled'])->toBeFalse();
    expect($dropdown['unlock']['disabled'])->toBeTrue();

    try {
        SeoPanelActions::perform($page, 'generate');
        test()->fail('Expected SEO panel action to redirect');
    } catch (Redirect $redirect) {
        expect($redirect->location())->toContain('/pages/about');
        expect($redirect->location())->not->toContain('?tab=seo');
    }

    $page = $kirby->site()->find('about');
    expect($page->content('de')->get('seoTitle')->value())->toBe('Panel de');
    expect($page->content('de')->get('seoDescription')->value())->toBe('Panel description de');

    try {
        SeoPanelActions::perform($page, 'lock');
        test()->fail('Expected SEO panel action to redirect');
    } catch (Redirect) {
    }

    $page = $kirby->site()->find('about');
    expect($page->content('de')->get('seoAiState')->value())->toBe('locked');

    $dropdown = SeoPanelActions::dropdown($page);
    expect($dropdown['lock']['disabled'])->toBeTrue();
    expect($dropdown['unlock']['disabled'])->toBeFalse();

    try {
        SeoPanelActions::perform($page, 'hide');
        test()->fail('Expected SEO panel action to redirect');
    } catch (Redirect) {
    }

    $page = $kirby->site()->find('about');
    expect($page->content('de')->get('seoIndex')->value())->toBe('0');
});

it('redirects site seo actions back to the site view', function (): void {
    $kirby = testKirby();
    $site = $kirby->site();

    expect(SeoPanelButtons::site($site)['options'])->toBe('seo/site');

    $dropdown = SeoPanelActions::dropdown($site);
    expect($dropdown)->toHaveKeys(['indexStatus', 'sitemapStatus', 'generate', 'unlock', 'lock']);
    expect(array_values(array_filter(array_keys($dropdown), 'is_string')))->toBe([
        'indexStatus',
        'sitemapStatus',
        'generate',
        'unlock',
        'lock',
    ]);
    expect($dropdown)->not->toHaveKey('refresh');
    expect($dropdown['indexStatus']['text'])->toBe('Search indexing: Allowed');
    expect($dropdown['sitemapStatus']['text'])->toBe('Sitemap: Included');
    expect($dropdown['indexStatus']['class'])->toBe('k-seo-dropdown-status k-seo-dropdown-status--ok');
    expect($dropdown['sitemapStatus']['class'])->toBe('k-seo-dropdown-status k-seo-dropdown-status--ok');
    expect($dropdown['indexStatus']['disabled'])->toBeTrue();
    expect($dropdown['sitemapStatus']['disabled'])->toBeTrue();
    expect($dropdown['indexStatus']['icon'])->toBe('robot');
    expect($dropdown['sitemapStatus']['icon'])->toBe('sitemap');
    expect($dropdown['lock']['link'])->toBe('seo/site/action/lock');
    expect($dropdown['unlock']['link'])->toBe('seo/site/action/unlock');
    expect($dropdown['lock']['disabled'])->toBeFalse();
    expect($dropdown['unlock']['disabled'])->toBeTrue();

    try {
        SeoPanelActions::perform($site, 'lock');
        test()->fail('Expected SEO panel action to redirect');
    } catch (Redirect $redirect) {
        expect($redirect->location())->toContain('/site');
        expect($redirect->location())->not->toContain('?tab=seo');
    }

    $site = $kirby->site();
    expect($site->content()->get('seoAiState')->value())->toBe('locked');

    $dropdown = SeoPanelActions::dropdown($site);
    expect($dropdown['lock']['disabled'])->toBeTrue();
    expect($dropdown['unlock']['disabled'])->toBeFalse();
});
