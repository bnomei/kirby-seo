<?php

declare(strict_types=1);

use Bnomei\Seo\Core\Ai\SeoAiResult;
use Bnomei\Seo\Kirby\Ai\SeoAiResultNormalizer;

it('normalizes arrays strings and result objects into seo ai results', function (): void {
    $fromArray = SeoAiResultNormalizer::normalize([
        'title' => 'Array title',
        'description' => 'Array description',
    ]);
    $fromString = SeoAiResultNormalizer::normalize('{"title":"String title","description":"String description"}');
    $fromObject = SeoAiResultNormalizer::normalize(new SeoAiResult(
        title: 'Object title',
        description: 'Object description',
    ));

    expect($fromArray->title)->toBe('Array title');
    expect($fromString->description)->toBe('String description');
    expect($fromObject->title)->toBe('Object title');
});
