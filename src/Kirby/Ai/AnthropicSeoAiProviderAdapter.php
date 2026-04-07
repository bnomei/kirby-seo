<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Ai;

use Bnomei\Seo\Core\Ai\SeoAiHttpRequest;
use Bnomei\Seo\Core\Ai\SeoAiHttpResponse;
use Bnomei\Seo\Core\Ai\SeoAiProviderAdapter;
use Bnomei\Seo\Core\Ai\SeoAiRequest;
use Bnomei\Seo\Core\Ai\SeoAiResult;

final class AnthropicSeoAiProviderAdapter implements SeoAiProviderAdapter
{
    public function __construct(
        #[\SensitiveParameter]
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $url = 'https://api.anthropic.com/v1/messages',
        private readonly int $timeout = 30,
    ) {}

    public function buildRequest(SeoAiRequest $request): SeoAiHttpRequest
    {
        return new SeoAiHttpRequest(
            url: $this->url,
            method: 'POST',
            headers: [
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ],
            body: [
                'model' => $this->model,
                'max_tokens' => 300,
                'system' => $request->systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $request->userPrompt],
                ],
            ],
            timeout: $this->timeout,
        );
    }

    public function parseResponse(SeoAiHttpResponse $response): SeoAiResult
    {
        $body = is_array($response->body) ? $response->body : [];
        $text = $body['content'][0]['text'] ?? '{}';

        return SeoAiResultNormalizer::normalize($text);
    }
}
