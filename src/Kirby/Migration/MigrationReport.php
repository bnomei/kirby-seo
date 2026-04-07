<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Migration;

final class MigrationReport
{
    /**
     * @param MigrationModelChange[] $modelChanges
     * @param MigrationFileChange[] $fileChanges
     * @param string[] $warnings
     * @param string[] $followUps
     */
    public function __construct(
        public readonly string $source,
        public readonly string $label,
        public readonly bool $applied,
        public readonly array $modelChanges,
        public readonly array $fileChanges,
        public readonly array $warnings,
        public readonly array $followUps,
    ) {}

    public static function fromPlan(MigrationPlan $plan, bool $applied): self
    {
        $warnings = [];

        foreach ($plan->modelChanges as $change) {
            foreach ($change->warnings as $warning) {
                $warnings[] = $warning;
            }
        }

        return new self(
            source: $plan->source,
            label: $plan->label,
            applied: $applied,
            modelChanges: $plan->modelChanges,
            fileChanges: $plan->fileChanges,
            warnings: array_values(array_unique($warnings)),
            followUps: array_values(array_unique($plan->followUps)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'label' => $this->label,
            'applied' => $this->applied,
            'modelChanges' => array_map(
                static fn(MigrationModelChange $change): array => $change->toArray(),
                $this->modelChanges,
            ),
            'fileChanges' => array_map(
                static fn(MigrationFileChange $change): array => $change->toArray(),
                $this->fileChanges,
            ),
            'warnings' => $this->warnings,
            'followUps' => $this->followUps,
        ];
    }

    /**
     * @return string[]
     */
    public function summaryLines(): array
    {
        $modelWrites = 0;

        foreach ($this->modelChanges as $change) {
            $modelWrites += count($change->writes);
        }

        return [
            'source: ' . $this->label,
            'mode: ' . ($this->applied ? 'apply' : 'dry-run'),
            'models: ' . count($this->modelChanges),
            'field writes: ' . $modelWrites,
            'files: ' . count($this->fileChanges),
            'warnings: ' . count($this->warnings),
            'follow-ups: ' . count($this->followUps),
        ];
    }
}
