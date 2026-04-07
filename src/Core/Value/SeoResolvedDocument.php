<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Value;

use Bnomei\Seo\Core\Contract\SeoValueObject;

final readonly class SeoResolvedDocument implements SeoValueObject
{
    public function __construct(
        public SeoContext $context,
        public SeoContentSnapshot $snapshot,
        public SeoRuleSet $rules,
        public SeoResolvedMeta $meta,
        public SeoResolvedState $state,
        public SeoSitemapResult $sitemap,
    ) {}

    public function toArray(): array
    {
        return [
            'context' => $this->context->toArray(),
            'snapshot' => $this->snapshot->toArray(),
            'rules' => $this->rules->toArray(),
            'meta' => $this->meta->toArray(),
            'state' => $this->state->toArray(),
            'sitemap' => $this->sitemap->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
