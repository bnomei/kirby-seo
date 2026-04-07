<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Ai;

use Bnomei\Seo\Core\Ai\SeoAiResult;
use InvalidArgumentException;

final class SeoAiResultNormalizer
{
    public static function normalize(mixed $value): SeoAiResult
    {
        if ($value instanceof SeoAiResult) {
            return $value;
        }

        if (is_string($value)) {
            $value = self::decodeString($value);
        }

        if (is_array($value) === false) {
            throw new InvalidArgumentException('AI result must normalize to an array or SeoAiResult.');
        }

        $title = $value['title'] ?? null;
        $description = $value['description'] ?? null;

        if (is_string($title) === false || is_string($description) === false) {
            throw new InvalidArgumentException('AI result must contain string title and description.');
        }

        return new SeoAiResult(
            title: trim($title),
            description: trim($description),
            meta: self::normalizeMeta($value['meta'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeString(string $value): array
    {
        $trimmed = trim($value);

        $decoded = json_decode(json: $trimmed, associative: true);

        if (is_array($decoded) === true) {
            return self::normalizeMeta($decoded);
        }

        if (preg_match('/\{.*\}/s', $trimmed, $matches) === 1) {
            $decoded = json_decode(json: $matches[0], associative: true);

            if (is_array($decoded) === true) {
                return self::normalizeMeta($decoded);
            }
        }

        throw new InvalidArgumentException('AI result string did not contain valid JSON.');
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalizeMeta(mixed $value): array
    {
        if (is_array($value) === false) {
            return [];
        }

        $meta = [];

        foreach ($value as $key => $item) {
            if (is_string($key) === true) {
                $meta[$key] = $item;
            }
        }

        return $meta;
    }
}
