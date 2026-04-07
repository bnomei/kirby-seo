<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Ai;

use JsonSerializable;

final readonly class SeoAiResult implements JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $title,
        public string $description,
        public array $meta = [],
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'meta' => $this->meta,
        ];
    }
}
