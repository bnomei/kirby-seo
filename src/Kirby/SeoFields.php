<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby;

final class SeoFields
{
    public const TITLE = 'seoTitle';
    public const DESCRIPTION = 'seoDescription';
    public const CANONICAL = 'seoCanonical';
    public const IMAGE = 'seoImage';
    public const AI_STATE = 'seoAiState';
    public const AI_SOURCE_HASH = 'seoAiSourceHash';
    public const AI_GENERATED_AT = 'seoAiGeneratedAt';
    public const AI_PROMPT_VERSION = 'seoAiPromptVersion';
    public const AI_LANGUAGE = 'seoAiLanguage';
    public const INDEX = 'seoIndex';

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::TITLE,
            self::DESCRIPTION,
            self::CANONICAL,
            self::IMAGE,
            self::AI_STATE,
            self::AI_SOURCE_HASH,
            self::AI_GENERATED_AT,
            self::AI_PROMPT_VERSION,
            self::AI_LANGUAGE,
            self::INDEX,
        ];
    }

    /**
     * @return string[]
     */
    public static function bookkeeping(): array
    {
        return [
            self::AI_STATE,
            self::AI_SOURCE_HASH,
            self::AI_GENERATED_AT,
            self::AI_PROMPT_VERSION,
            self::AI_LANGUAGE,
            self::INDEX,
        ];
    }

    /**
     * @return string[]
     */
    public static function migratable(): array
    {
        return [
            self::TITLE,
            self::DESCRIPTION,
            self::CANONICAL,
            self::IMAGE,
            self::INDEX,
        ];
    }

    /**
     * @return string[]
     */
    public static function legacyFields(string $field): array
    {
        return match ($field) {
            self::TITLE => ['metaTitle', 'meta_title'],
            self::DESCRIPTION => ['metaDescription', 'meta_description'],
            self::CANONICAL => ['meta_canonical_url'],
            self::IMAGE => ['ogImage', 'og_image'],
            self::INDEX => ['robotsIndex', 'robots_index', 'robots'],
            default => [],
        };
    }

    /**
     * @return string[]
     */
    public static function contentCandidates(string $field): array
    {
        return array_values(array_unique([$field, ...self::legacyFields($field)]));
    }
}
