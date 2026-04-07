<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Migration;

use Kirby\Cms\Page;
use Kirby\Cms\Site;

final class MigrationModelChange
{
    /**
     * @param array<string, string> $writes
     * @param array<string, string> $skipped
     * @param string[] $warnings
     */
    public function __construct(
        private readonly Page|Site $model,
        public readonly string $modelType,
        public readonly string $modelId,
        public readonly string $languageCode,
        public readonly array $writes = [],
        public readonly array $skipped = [],
        public readonly array $warnings = [],
    ) {}

    /**
     * @param array<string, string> $writes
     * @param array<string, string> $skipped
     * @param string[] $warnings
     */
    public static function fromModel(
        Page|Site $model,
        string $languageCode,
        array $writes,
        array $skipped,
        array $warnings,
    ): self {
        return new self(
            model: $model,
            modelType: $model instanceof Site ? 'site' : 'page',
            modelId: $model instanceof Site ? 'site' : $model->id(),
            languageCode: $languageCode,
            writes: $writes,
            skipped: $skipped,
            warnings: $warnings,
        );
    }

    public function model(): Page|Site
    {
        return $this->model;
    }

    public function hasWrites(): bool
    {
        return $this->writes !== [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'modelType' => $this->modelType,
            'modelId' => $this->modelId,
            'languageCode' => $this->languageCode,
            'writes' => $this->writes,
            'skipped' => $this->skipped,
            'warnings' => $this->warnings,
        ];
    }
}
