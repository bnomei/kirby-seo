<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Migration;

final class MigrationPlan
{
    /**
     * @param MigrationModelChange[] $modelChanges
     * @param MigrationFileChange[] $fileChanges
     * @param string[] $followUps
     */
    public function __construct(
        public readonly string $source,
        public readonly string $label,
        public readonly array $modelChanges,
        public readonly array $fileChanges,
        public readonly array $followUps,
    ) {}
}
