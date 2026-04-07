<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Ai;

final class PromptTemplateRenderer
{
    /**
     * @param array<string, scalar|null> $variables
     */
    public function render(string $template, array $variables): string
    {
        $replacements = [];

        foreach ($variables as $key => $value) {
            $replacements['{{' . $key . '}}'] = (string) ($value ?? '');
        }

        return trim(strtr($template, $replacements));
    }
}
