<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Ai;

final class SeoAiRequestBuilder
{
    public function __construct(
        private readonly PromptTemplateRenderer $promptTemplateRenderer = new PromptTemplateRenderer(),
        private readonly SourceHashBuilder $sourceHashBuilder = new SourceHashBuilder(),
    ) {}

    /**
     * @param array<string, scalar|null> $variables
     * @param array<string, mixed> $settings
     */
    public function build(
        string $operation,
        SeoAiSnapshot $snapshot,
        string $systemPromptTemplate,
        string $userPromptTemplate,
        array $variables = [],
        array $settings = [],
    ): SeoAiRequest {
        $languageVariables = [
            'languageCode' => $snapshot->languageCode,
            'languageName' => $snapshot->languageName,
            'url' => $snapshot->url,
            'title' => $snapshot->title,
            'excerpt' => $snapshot->excerpt,
            'content' => $snapshot->text,
        ];

        $variables = [...$languageVariables, ...$variables];

        return new SeoAiRequest(
            operation: $operation,
            languageCode: $snapshot->languageCode,
            languageName: $snapshot->languageName,
            systemPrompt: $this->promptTemplateRenderer->render($systemPromptTemplate, $variables),
            userPrompt: $this->promptTemplateRenderer->render($userPromptTemplate, $variables),
            snapshot: $snapshot,
            sourceHash: $this->sourceHashBuilder->build($snapshot),
            variables: $variables,
            settings: $settings,
        );
    }
}
