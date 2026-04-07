<?php

declare(strict_types=1);

use Bnomei\Seo\Core\Ai\SeoAiHttpResponse;
use Bnomei\Seo\Core\Ai\SeoAiRequest;
use Bnomei\Seo\Core\Ai\SeoAiSnapshot;
use Bnomei\Seo\Kirby\Ai\AnthropicSeoAiProviderAdapter;
use Bnomei\Seo\Kirby\Ai\GeminiSeoAiProviderAdapter;
use Bnomei\Seo\Kirby\Ai\OpenAiSeoAiProviderAdapter;

function fakeSeoAiRequest(): SeoAiRequest
{
    return new SeoAiRequest(
        operation: 'page-meta',
        languageCode: 'en',
        languageName: 'English',
        systemPrompt: 'system',
        userPrompt: 'user',
        snapshot: SeoAiSnapshot::fromArray([
            'kind' => 'page',
            'id' => 'about',
            'slug' => 'about',
            'uri' => 'about',
            'template' => 'default',
            'url' => 'https://example.com/about',
            'title' => 'About',
            'excerpt' => 'About excerpt',
            'text' => 'About body',
            'fields' => [],
            'languageCode' => 'en',
            'languageName' => 'English',
            'visibility' => 'listed',
        ]),
        sourceHash: 'hash',
    );
}

it('builds and parses openai responses payloads', function (): void {
    $adapter = new OpenAiSeoAiProviderAdapter('key', 'gpt-5-mini');
    $request = $adapter->buildRequest(fakeSeoAiRequest());
    $result = $adapter->parseResponse(new SeoAiHttpResponse(
        statusCode: 200,
        body: ['output_text' => '{"title":"OpenAI","description":"Result"}'],
    ));

    expect($request->url)->toContain('/responses');
    expect($result->title)->toBe('OpenAI');
});

it('parses anthropic responses payloads', function (): void {
    $adapter = new AnthropicSeoAiProviderAdapter('key', 'claude-sonnet-4-5');
    $result = $adapter->parseResponse(new SeoAiHttpResponse(
        statusCode: 200,
        body: ['content' => [['text' => '{"title":"Anthropic","description":"Result"}']]],
    ));

    expect($result->title)->toBe('Anthropic');
});

it('parses gemini responses payloads', function (): void {
    $adapter = new GeminiSeoAiProviderAdapter('key', 'gemini-2.5-flash');
    $result = $adapter->parseResponse(new SeoAiHttpResponse(
        statusCode: 200,
        body: [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => '{"title":"Gemini","description":"Result"}'],
                        ],
                    ],
                ],
            ],
        ],
    ));

    expect($result->title)->toBe('Gemini');
});
