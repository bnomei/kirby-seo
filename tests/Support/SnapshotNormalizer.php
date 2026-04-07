<?php

declare(strict_types=1);

namespace Bnomei\Seo\Tests\Support;

use JsonSerializable;

final class SnapshotNormalizer
{
    public static function normalize(mixed $value): string
    {
        if ($value instanceof JsonSerializable) {
            $value = $value->jsonSerialize();
        }

        if (is_array($value)) {
            $value = self::normalizeArray($value);
        }

        if (is_string($value) === false) {
            $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return str_replace(
            [dirname(__DIR__, 2), "\r\n"],
            ['<ROOT>', "\n"],
            (string)$value
        );
    }

    /**
     * @param array<mixed> $value
     * @return array<mixed>
     */
    private static function normalizeArray(array $value): array
    {
        foreach ($value as $key => $item) {
            if ($item instanceof JsonSerializable) {
                $item = $item->jsonSerialize();
            }

            if (is_array($item)) {
                $value[$key] = self::normalizeArray($item);
                continue;
            }

            if (is_string($item)) {
                $value[$key] = str_replace(
                    [dirname(__DIR__, 2), "\r\n"],
                    ['<ROOT>', "\n"],
                    $item
                );
            }
        }

        return $value;
    }
}
