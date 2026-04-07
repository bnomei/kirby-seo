<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Value;

use Bnomei\Seo\Core\Contract\SeoValueObject;
use DateTimeImmutable;

final readonly class SeoSitemapEntry implements SeoValueObject
{
    /**
     * @param array<string, string> $alternates
     */
    public function __construct(
        public string $loc,
        public ?DateTimeImmutable $lastModifiedAt = null,
        public array $alternates = [],
    ) {}

    public function toArray(): array
    {
        return [
            'loc' => $this->loc,
            'lastModifiedAt' => $this->lastModifiedAt?->format(DATE_ATOM),
            'alternates' => $this->alternates,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
