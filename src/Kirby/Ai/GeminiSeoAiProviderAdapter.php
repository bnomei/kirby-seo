<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Ai;

use Bnomei\Seo\Core\Ai\SeoAiHttpRequest;
use Bnomei\Seo\Core\Ai\SeoAiHttpResponse;
use Bnomei\Seo\Core\Ai\SeoAiProviderAdapter;
use Bnomei\Seo\Core\Ai\SeoAiRequest;
use Bnomei\Seo\Core\Ai\SeoAiResult;

final class GeminiSeoAiProviderAdapter implements SeoAiProviderAdapter
{
    public function __construct(
        #[\SensitiveParameter]
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models',
        private readonly int $timeout = 30,
    ) {}

    public function buildRequest(SeoAiRequest $request): SeoAiHttpRequest
    {
        return new SeoAiHttpRequest(
            url: rtrim(string: $this->baseUrl, characters: '/')
            . '/'
            . $this->model
            . ':generateContent?key='
            . $this->apiKey,
            method: 'POST',
            headers: [
                'Content-Type' => 'application/json',
            ],
            body: [
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $request->systemPrompt],
                    ],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $request->userPrompt],
                        ],
                    ],
                ],
            ],
            timeout: $this->timeout,
        );
    }

    public function parseResponse(SeoAiHttpResponse $response): SeoAiResult
    {
        $body = is_array($response->body) ? $response->body : [];
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        return SeoAiResultNormalizer::normalize($text);
    }
}
