<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Ai;

final class SourceHashBuilder
{
    public function build(SeoAiSnapshot $snapshot): string
    {
        $data = $snapshot->jsonSerialize();
        $ownedFieldKeys = [
            'seoTitle',
            'seoDescription',
            'seoAiState',
            'seoAiSourceHash',
            'seoAiGeneratedAt',
            'seoAiPromptVersion',
            'seoAiLanguage',
            'seoIndex',
        ];

        unset(
            $data['aiTitle'],
            $data['aiDescription'],
            $data['aiState'],
            $data['aiSourceHash'],
            $data['aiGeneratedAt'],
            $data['aiPromptVersion'],
            $data['aiLanguage'],
        );

        if (array_key_exists('fields', $data) === true && is_array($data['fields']) === true) {
            foreach ($ownedFieldKeys as $key) {
                unset($data['fields'][$key]);
            }
        }

        return hash('sha256', json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));
    }
}
