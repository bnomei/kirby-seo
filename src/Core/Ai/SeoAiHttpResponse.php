<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Ai;

use JsonSerializable;

final readonly class SeoAiHttpResponse implements JsonSerializable
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed>|string|null $body
     */
    public function __construct(
        public int $statusCode,
        public array $headers = [],
        public array|string|null $body = null,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'statusCode' => $this->statusCode,
            'headers' => $this->headers,
            'body' => $this->body,
        ];
    }
}
