<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Ai;

use Closure;

final class SeoAiHookGuard
{
    /**
     * @var array<string, bool>
     */
    private static array $active = [];

    public static function active(string $key): bool
    {
        return self::$active[$key] ?? false;
    }

    public static function run(string $key, Closure $callback): mixed
    {
        self::$active[$key] = true;

        try {
            return $callback();
        } finally {
            unset(self::$active[$key]);
        }
    }
}
