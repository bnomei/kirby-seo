<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Service;

use Bnomei\Seo\Core\Value\SeoContentSnapshot;
use Bnomei\Seo\Core\Value\SeoContext;
use Bnomei\Seo\Core\Value\SeoResolvedDocument;
use Bnomei\Seo\Core\Value\SeoResolvedState;
use Bnomei\Seo\Core\Value\SeoRuleSet;

final class SeoResolver
{
    public function __construct(
        private readonly IndexabilityResolver $indexabilityResolver = new IndexabilityResolver(),
        private readonly MetaResolver $metaResolver = new MetaResolver(),
        private readonly SitemapResolver $sitemapResolver = new SitemapResolver(),
    ) {}

    public function resolve(
        SeoContext $context,
        SeoContentSnapshot $snapshot,
        ?SeoRuleSet $rules = null,
    ): SeoResolvedDocument {
        $rules ??= SeoRuleSet::defaults();

        $indexability = $this->indexabilityResolver->resolve($context, $snapshot, $rules);

        $state = new SeoResolvedState(
            indexable: $indexability['indexable'],
            indexabilityReason: $indexability['reason'],
            sitemapIncluded: false,
            sitemapReason: \Bnomei\Seo\Core\Value\SeoSitemapReason::VisibilityExcluded,
            visibility: $context->visibility,
            language: $context->language,
            lastModifiedAt: $snapshot->modifiedAt,
        );

        $meta = $this->metaResolver->resolve($context, $snapshot, $rules, $state);
        $sitemap = $this->sitemapResolver->resolve($context, $snapshot, $rules, $state, $meta->canonicalUrl);

        $state = new SeoResolvedState(
            indexable: $state->indexable,
            indexabilityReason: $state->indexabilityReason,
            sitemapIncluded: $sitemap->included,
            sitemapReason: $sitemap->reason,
            visibility: $state->visibility,
            language: $state->language,
            lastModifiedAt: $state->lastModifiedAt,
        );

        return new SeoResolvedDocument(
            context: $context,
            snapshot: $snapshot,
            rules: $rules,
            meta: $meta,
            state: $state,
            sitemap: $sitemap,
        );
    }
}
