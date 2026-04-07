<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Migration;

use Kirby\Filesystem\F;

final class MigrationFileChange
{
    /**
     * @param string[] $rewrites
     */
    public function __construct(
        public readonly string $path,
        public readonly string $kind,
        public readonly array $rewrites,
        private readonly string $updatedContent,
    ) {}

    public function apply(): void
    {
        F::write($this->path, $this->updatedContent);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'kind' => $this->kind,
            'rewrites' => $this->rewrites,
        ];
    }
}
