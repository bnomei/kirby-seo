<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Ai;

use JsonSerializable;

final readonly class SeoAiHttpRequest implements JsonSerializable
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed>|string $body
     */
    public function __construct(
        public string $url,
        public string $method,
        public array $headers,
        public array|string $body,
        public int $timeout = 30,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'url' => $this->url,
            'method' => $this->method,
            'headers' => $this->headers,
            'body' => $this->body,
            'timeout' => $this->timeout,
        ];
    }
}
