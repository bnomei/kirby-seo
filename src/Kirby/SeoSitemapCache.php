<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby;

use Kirby\Cache\Cache;
use Kirby\Cache\FileCache;
use Kirby\Cms\App;

final class SeoSitemapCache
{
    private const CACHE_NAME = 'bnomei.seo.sitemap';
    private const KEY_PREFIX = 'xml:';

    public function __construct(
        private readonly App $kirby,
        private readonly ?SeoOptionReader $options = null,
    ) {}

    public static function for(App $kirby): self
    {
        return new self($kirby, new SeoOptionReader($kirby));
    }

    public function get(?string $languageCode = null): ?string
    {
        $xml = $this->cache()->get($this->key($languageCode));

        return is_string($xml) ? $xml : null;
    }

    public function set(string $xml, ?string $languageCode = null): string
    {
        $this->cache()->set($this->key($languageCode), $xml, $this->minutes());

        return $xml;
    }

    public function flush(): void
    {
        SeoRuntimeBridge::resetDocumentCache();
        $cache = $this->cache();

        if ($cache instanceof FileCache) {
            $root = $cache->root();

            if (is_dir($root) === false) {
                return;
            }

            set_error_handler(static function (int $severity, string $message) use ($root): bool {
                return (
                    $severity === E_WARNING
                    && str_contains($message, 'rmdir(' . $root)
                    && str_contains($message, 'No such file or directory')
                );
            });

            try {
                $cache->flush();
            } finally {
                restore_error_handler();
            }

            return;
        }

        $cache->flush();
    }

    private function cache(): Cache
    {
        return $this->kirby->cache(self::CACHE_NAME);
    }

    private function key(?string $languageCode = null): string
    {
        $languageCode ??= $this->kirby->language()?->code() ?? $this->kirby->defaultLanguage()?->code() ?? 'en';

        return self::KEY_PREFIX . $languageCode;
    }

    private function minutes(): int
    {
        return $this->options()->sitemapCacheMinutes(default: 7 * 24 * 60);
    }

    private function options(): SeoOptionReader
    {
        return $this->options ?? new SeoOptionReader($this->kirby);
    }
}
