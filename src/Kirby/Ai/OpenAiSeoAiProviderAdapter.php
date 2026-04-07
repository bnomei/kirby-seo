<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Ai;

use Bnomei\Seo\Core\Ai\SeoAiHttpRequest;
use Bnomei\Seo\Core\Ai\SeoAiHttpResponse;
use Bnomei\Seo\Core\Ai\SeoAiProviderAdapter;
use Bnomei\Seo\Core\Ai\SeoAiRequest;
use Bnomei\Seo\Core\Ai\SeoAiResult;

final class OpenAiSeoAiProviderAdapter implements SeoAiProviderAdapter
{
    public function __construct(
        #[\SensitiveParameter]
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $url = 'https://api.openai.com/v1/responses',
        private readonly int $timeout = 30,
    ) {}

    public function buildRequest(SeoAiRequest $request): SeoAiHttpRequest
    {
        return new SeoAiHttpRequest(
            url: $this->url,
            method: 'POST',
            headers: [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            body: [
                'model' => $this->model,
                'input' => [
                    ['role' => 'system', 'content' => $request->systemPrompt],
                    ['role' => 'user', 'content' => $request->userPrompt],
                ],
            ],
            timeout: $this->timeout,
        );
    }

    public function parseResponse(SeoAiHttpResponse $response): SeoAiResult
    {
        $body = is_array($response->body) ? $response->body : [];
        $text = $body['output_text'] ?? null;

        if (is_string($text) === false) {
            $text = $body['output'][0]['content'][0]['text'] ?? '{}';
        }

        return SeoAiResultNormalizer::normalize($text);
    }
}
