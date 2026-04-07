<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Ai;

use JsonSerializable;

final readonly class SeoAiRequest implements JsonSerializable
{
    /**
     * @param array<string, mixed> $variables
     * @param array<string, mixed> $settings
     */
    public function __construct(
        public string $operation,
        public string $languageCode,
        public string $languageName,
        public string $systemPrompt,
        public string $userPrompt,
        public SeoAiSnapshot $snapshot,
        public string $sourceHash,
        public array $variables = [],
        public array $settings = [],
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'operation' => $this->operation,
            'languageCode' => $this->languageCode,
            'languageName' => $this->languageName,
            'systemPrompt' => $this->systemPrompt,
            'userPrompt' => $this->userPrompt,
            'snapshot' => $this->snapshot,
            'sourceHash' => $this->sourceHash,
            'variables' => $this->variables,
            'settings' => $this->settings,
        ];
    }
}
