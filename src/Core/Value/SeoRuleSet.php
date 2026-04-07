<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Value;

use Bnomei\Seo\Core\Contract\SeoValueObject;

final readonly class SeoRuleSet implements SeoValueObject
{
    /**
     * @param array<string, string> $metaTags
     * @param list<string> $sitemapAllowedVisibilities
     * @param list<string> $robotsWhenIndexable
     * @param list<string> $robotsWhenBlocked
     */
    public function __construct(
        public ?string $titleSuffix = null,
        public string $titleSeparator = ' | ',
        public ?string $defaultDescription = null,
        public ?string $canonicalUrl = null,
        public array $metaTags = [],
        public ?bool $indexable = null,
        public bool $templateExcludedFromIndex = false,
        public ?bool $sitemapIncluded = null,
        public bool $templateExcludedFromSitemap = false,
        public bool $sitemapRequireCanonical = true,
        public array $sitemapAllowedVisibilities = [SeoVisibility::Listed->value],
        public array $robotsWhenIndexable = ['index', 'follow'],
        public array $robotsWhenBlocked = ['noindex', 'nofollow'],
    ) {}

    public static function defaults(): self
    {
        return new self();
    }

    public function allowsVisibility(SeoVisibility $visibility): bool
    {
        return in_array(needle: $visibility->value, haystack: $this->sitemapAllowedVisibilities, strict: true);
    }

    public function toArray(): array
    {
        return [
            'titleSuffix' => $this->titleSuffix,
            'titleSeparator' => $this->titleSeparator,
            'defaultDescription' => $this->defaultDescription,
            'canonicalUrl' => $this->canonicalUrl,
            'metaTags' => $this->metaTags,
            'indexable' => $this->indexable,
            'templateExcludedFromIndex' => $this->templateExcludedFromIndex,
            'sitemapIncluded' => $this->sitemapIncluded,
            'templateExcludedFromSitemap' => $this->templateExcludedFromSitemap,
            'sitemapRequireCanonical' => $this->sitemapRequireCanonical,
            'sitemapAllowedVisibilities' => $this->sitemapAllowedVisibilities,
            'robotsWhenIndexable' => $this->robotsWhenIndexable,
            'robotsWhenBlocked' => $this->robotsWhenBlocked,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
