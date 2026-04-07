<?php

declare(strict_types=1);

use Kirby\Toolkit\I18n;

it('registers plugin translations for php consumers', function (): void {
    testKirby();

    expect(I18n::translate('bnomei.seo.button.generate', null, 'en'))->toBe('Generate');
    expect(I18n::translate('bnomei.seo.button.generate', null, 'de'))->toBe('Generieren');
    expect(I18n::translate('bnomei.seo.section.preview.headline', null, 'en'))->toBe('Search preview');
});
