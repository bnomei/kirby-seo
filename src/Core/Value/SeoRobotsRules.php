<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Value;

use Bnomei\Seo\Core\Contract\SeoValueObject;

final readonly class SeoRobotsRules implements SeoValueObject
{
    /**
     * @param list<SeoRobotsRuleGroup> $groups
     * @param list<string> $sitemapUrls
     */
    public function __construct(
        public array $groups = [],
        public array $sitemapUrls = [],
    ) {}

    public function toArray(): array
    {
        return [
            'groups' => array_map(static fn(SeoRobotsRuleGroup $group): array => $group->toArray(), $this->groups),
            'sitemapUrls' => $this->sitemapUrls,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
