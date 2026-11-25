<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Support;

use DateTimeImmutable;

final class Timestamp
{
    /**
     * Convert a modification time to UTC, or return null if the version has no timestamp.
     */
    public static function toDateTimeImmutable(int|string|false|null $timestamp): ?DateTimeImmutable
    {
        if ($timestamp === false || $timestamp === null || $timestamp === '' || (int) $timestamp <= 0) {
            return null;
        }

        return new DateTimeImmutable('@' . (int) $timestamp);
    }
}
