<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Support;

use DateTimeImmutable;

final class Timestamp
{
    /**
     * Convert a Kirby model/version mtime into a UTC DateTimeImmutable.
     *
     * Kirby\Cms\Page::modified() is typed as int|string|false|null
     * (null when a content version has no mtime).
     */
    public static function toDateTimeImmutable(int|string|false|null $timestamp): ?DateTimeImmutable
    {
        if ($timestamp === false || $timestamp === null || $timestamp === '' || (int) $timestamp <= 0) {
            return null;
        }

        return new DateTimeImmutable('@' . (int) $timestamp);
    }
}
