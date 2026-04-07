<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby;

use Closure;
use Kirby\Cms\App;

final class SeoOptionReader
{
    /**
     * @var array<string, string[]>
     */
    private const LEGACY_OPTIONS = [
        'robots.active' => [
            'tobimori.seo.robots.enabled',
            'tobimori.seo.robots.active',
            'fabianmichael.meta.robots',
        ],
        'robots.index' => [
            'tobimori.seo.robots.index',
            'fabianmichael.meta.robots.index',
        ],
        'robots.txt.sitemap' => [
            'tobimori.seo.robots.sitemap',
        ],
        'sitemap.active' => [
            'tobimori.seo.sitemap.enabled',
            'tobimori.seo.sitemap.active',
            'fabianmichael.meta.sitemap',
        ],
        'sitemap.ignoreTemplates' => [
            'tobimori.seo.sitemap.excludeTemplates',
            'fabianmichael.meta.sitemap.templates.exclude',
        ],
    ];

    public function __construct(
        private readonly App $kirby,
    ) {}

    public function enabled(): bool
    {
        return $this->bool('enabled', true);
    }

    public function raw(string $key, mixed $default = null): mixed
    {
        $sentinel = new \stdClass();
        $value = $this->kirby->option($this->name($key), $sentinel);

        if ($value !== $sentinel) {
            return $value;
        }

        foreach (self::LEGACY_OPTIONS[$key] ?? [] as $legacyKey) {
            $legacy = $this->kirby->option($legacyKey, $sentinel);

            if ($legacy !== $sentinel) {
                return $legacy;
            }
        }

        return $default;
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function resolve(string $key, array $arguments = [], mixed $default = null): mixed
    {
        $value = $this->raw($key, $default);

        if ($value instanceof Closure) {
            return $value(...$arguments);
        }

        return $value;
    }

    /**
     * @param array<int, mixed> $arguments
     * @return array<mixed>
     */
    public function array(string $key, array $arguments = [], array $default = []): array
    {
        $value = $this->resolve($key, $arguments, $default);

        return is_array($value) ? $value : $default;
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function bool(string $key, bool $default = false, array $arguments = []): bool
    {
        $value = $this->resolve($key, $arguments, $default);

        return is_bool($value) ? $value : $default;
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function string(string $key, ?string $default = null, array $arguments = []): ?string
    {
        $value = $this->resolve($key, $arguments, $default);

        return is_string($value) ? $value : $default;
    }

    /**
     * @param array<int, mixed> $arguments
     * @return array<mixed>
     */
    public function metaDefaults(array $arguments = []): array
    {
        return $this->array('meta.defaults', $arguments);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function metaTitle(array $arguments = [], ?string $default = null): ?string
    {
        return $this->string('meta.title', $default, $arguments);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function metaDescription(array $arguments = [], ?string $default = null): ?string
    {
        return $this->string('meta.description', $default, $arguments);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function metaCanonical(array $arguments = [], ?string $default = null): ?string
    {
        return $this->string('meta.canonical', $default, $arguments);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function metaImage(array $arguments = [], ?string $default = null): ?string
    {
        return $this->string('meta.image', $default, $arguments);
    }

    /**
     * @param array<int, mixed> $arguments
     * @return array<mixed>
     */
    public function metaAlternates(array $arguments = [], array $default = []): array
    {
        return $this->array('meta.alternates', $arguments, $default);
    }

    /**
     * @param array<int, mixed> $arguments
     * @return array<mixed>
     */
    public function metaTags(array $arguments = [], array $default = []): array
    {
        return $this->array('meta.tags', $arguments, $default);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function robotsActive(array $arguments = [], bool $default = true): bool
    {
        return $this->bool('robots.active', $default, $arguments);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function robotsFollowPageStatus(array $arguments = [], bool $default = true): bool
    {
        return $this->bool('robots.followPageStatus', $default, $arguments);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function robotsIndex(array $arguments = [], bool $default = true): bool
    {
        return $this->bool('robots.index', $default, $arguments);
    }

    /**
     * @param array<int, mixed> $arguments
     * @return array<mixed>
     */
    public function robotsMeta(array $arguments = [], array $default = []): array
    {
        return $this->array('robots.meta', $arguments, $default);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function robotsTxtSitemap(array $arguments = [], ?string $default = null): ?string
    {
        return $this->string('robots.txt.sitemap', $default, $arguments);
    }

    /**
     * @param array<int, mixed> $arguments
     * @return array<mixed>
     */
    public function robotsTxtGroups(array $arguments = [], array $default = []): array
    {
        return $this->array('robots.txt.groups', $arguments, $default);
    }

    /**
     * @param array<int, mixed> $arguments
     * @return array<mixed>
     */
    public function robotsTxtFilter(array $arguments = [], array $default = []): array
    {
        return $this->array('robots.txt.filter', $arguments, $default);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function sitemapActive(array $arguments = [], bool $default = true): bool
    {
        return $this->bool('sitemap.active', $default, $arguments);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function sitemapInclude(array $arguments = [], bool $default = true): bool
    {
        return $this->bool('sitemap.include', $default, $arguments);
    }

    /**
     * @param array<int, mixed> $arguments
     * @return array<mixed>
     */
    public function sitemapIgnoreTemplates(array $arguments = [], array $default = []): array
    {
        return $this->array('sitemap.ignoreTemplates', $arguments, $default);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function sitemapGenerator(array $arguments = [], mixed $default = null): mixed
    {
        return $this->resolve('sitemap.generator', $arguments, $default);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function sitemapCacheMinutes(array $arguments = [], int $default = 0): int
    {
        $value = $this->resolve('sitemap.cache.minutes', $arguments, $default);

        if (is_int($value) === true) {
            return max(0, $value);
        }

        if (is_numeric($value) === true) {
            return max(0, (int) $value);
        }

        return max(0, $default);
    }

    /**
     * @param array<int, mixed> $arguments
     */
    public function aiSource(array $arguments = [], ?string $default = null): ?string
    {
        return $this->string('ai.source', $default, $arguments);
    }

    private function name(string $key): string
    {
        return 'bnomei.seo.' . ltrim($key, '.');
    }
}
