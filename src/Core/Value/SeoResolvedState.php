<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Value;

use Bnomei\Seo\Core\Contract\SeoValueObject;
use DateTimeImmutable;

final readonly class SeoResolvedState implements SeoValueObject
{
    public function __construct(
        public bool $indexable,
        public SeoIndexabilityReason $indexabilityReason,
        public bool $sitemapIncluded,
        public SeoSitemapReason $sitemapReason,
        public SeoVisibility $visibility,
        public string $language,
        public ?DateTimeImmutable $lastModifiedAt = null,
    ) {}

    public function toArray(): array
    {
        return [
            'indexable' => $this->indexable,
            'indexabilityReason' => $this->indexabilityReason->value,
            'sitemapIncluded' => $this->sitemapIncluded,
            'sitemapReason' => $this->sitemapReason->value,
            'visibility' => $this->visibility->value,
            'language' => $this->language,
            'lastModifiedAt' => $this->lastModifiedAt?->format(DATE_ATOM),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
