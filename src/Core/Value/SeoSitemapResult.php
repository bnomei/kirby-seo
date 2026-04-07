<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Value;

use Bnomei\Seo\Core\Contract\SeoValueObject;

final readonly class SeoSitemapResult implements SeoValueObject
{
    public function __construct(
        public bool $included,
        public SeoSitemapReason $reason,
        public ?SeoSitemapEntry $entry = null,
    ) {}

    public function toArray(): array
    {
        return [
            'included' => $this->included,
            'reason' => $this->reason->value,
            'entry' => $this->entry?->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
