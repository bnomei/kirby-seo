<?php

declare(strict_types=1);

use Bnomei\Seo\Core\Ai\SeoAiRequestBuilder;
use Bnomei\Seo\Core\Ai\SeoAiSnapshot;
use Bnomei\Seo\Core\Ai\SourceHashBuilder;

it('builds explicit-language ai requests with deterministic hashes', function (): void {
    $snapshot = SeoAiSnapshot::fromArray([
        'kind' => 'page',
        'id' => 'about',
        'slug' => 'about',
        'uri' => 'about',
        'template' => 'default',
        'url' => 'https://example.com/about',
        'title' => 'About',
        'excerpt' => 'Excerpt',
        'text' => 'Body',
        'fields' => ['text' => 'Body'],
        'languageCode' => 'de',
        'languageName' => 'Deutsch',
        'visibility' => 'listed',
    ]);

    $builder = new SeoAiRequestBuilder();
    $request = $builder->build(
        operation: 'page-meta',
        snapshot: $snapshot,
        systemPromptTemplate: 'System {{languageCode}}',
        userPromptTemplate: 'Page {{title}} at {{url}} with {{content}}',
    );

    expect($request->languageCode)->toBe('de');
    expect($request->systemPrompt)->toBe('System de');
    expect($request->userPrompt)->toContain('https://example.com/about');
    expect($request->userPrompt)->toContain('Body');
    expect($request->variables['content'])->toBe('Body');
    expect($request->sourceHash)->toBe((new SourceHashBuilder())->build($snapshot));
});
