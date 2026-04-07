<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Ai;

use Bnomei\Seo\Core\Ai\SeoAiHttpRequest;
use Bnomei\Seo\Core\Ai\SeoAiHttpResponse;
use Bnomei\Seo\Core\Ai\SeoAiTransport;
use Closure;

final class CallbackSeoAiTransport implements SeoAiTransport
{
    public function __construct(
        private readonly Closure $callback,
    ) {}

    public function send(SeoAiHttpRequest $request): SeoAiHttpResponse
    {
        $response = ($this->callback)($request);

        if ($response instanceof SeoAiHttpResponse) {
            return $response;
        }

        if (is_array($response) === true) {
            return new SeoAiHttpResponse(
                statusCode: (int) ($response['statusCode'] ?? 200),
                headers: $this->headers($response['headers'] ?? null),
                body: $this->body($response['body'] ?? null),
            );
        }

        return new SeoAiHttpResponse(statusCode: 200, body: $this->body($response));
    }

    /**
     * @return array<string, string>
     */
    private function headers(mixed $headers): array
    {
        if (is_array($headers) === false) {
            return [];
        }

        $normalized = [];

        foreach ($headers as $name => $value) {
            if (is_string($name) && is_string($value)) {
                $normalized[$name] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>|string|null
     */
    private function body(mixed $body): array|string|null
    {
        if (is_string($body) || $body === null) {
            return $body;
        }

        if (is_scalar($body) === true) {
            return (string) $body;
        }

        if (is_array($body) === false) {
            return null;
        }

        $normalized = [];

        foreach ($body as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
