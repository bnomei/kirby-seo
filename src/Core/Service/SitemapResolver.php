<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Service;

use Bnomei\Seo\Core\Value\SeoContentSnapshot;
use Bnomei\Seo\Core\Value\SeoContext;
use Bnomei\Seo\Core\Value\SeoResolvedState;
use Bnomei\Seo\Core\Value\SeoRuleSet;
use Bnomei\Seo\Core\Value\SeoSitemapEntry;
use Bnomei\Seo\Core\Value\SeoSitemapReason;
use Bnomei\Seo\Core\Value\SeoSitemapResult;
use Bnomei\Seo\Core\Value\SeoVisibility;

final class SitemapResolver
{
    public function resolve(
        SeoContext $context,
        SeoContentSnapshot $snapshot,
        SeoRuleSet $rules,
        SeoResolvedState $state,
        ?string $canonicalUrl,
    ): SeoSitemapResult {
        if ($rules->sitemapIncluded !== null) {
            return $rules->sitemapIncluded
                ? new SeoSitemapResult(
                    included: true,
                    reason: SeoSitemapReason::ConfigIncluded,
                    entry: $this->entry($canonicalUrl, $snapshot),
                )
                : new SeoSitemapResult(included: false, reason: SeoSitemapReason::ConfigExcluded);
        }

        if ($context->visibility === SeoVisibility::Draft) {
            return new SeoSitemapResult(false, SeoSitemapReason::Draft);
        }

        if ($state->indexable === false) {
            return new SeoSitemapResult(false, SeoSitemapReason::IndexingBlocked);
        }

        if ($rules->templateExcludedFromSitemap === true) {
            return new SeoSitemapResult(false, SeoSitemapReason::TemplateExcluded);
        }

        if ($rules->allowsVisibility($context->visibility) === false) {
            return new SeoSitemapResult(false, SeoSitemapReason::VisibilityExcluded);
        }

        if ($rules->sitemapRequireCanonical === true && $canonicalUrl === null) {
            return new SeoSitemapResult(false, SeoSitemapReason::MissingCanonical);
        }

        return new SeoSitemapResult(
            included: true,
            reason: SeoSitemapReason::Included,
            entry: $this->entry($canonicalUrl ?? $context->url, $snapshot),
        );
    }

    private function entry(?string $loc, SeoContentSnapshot $snapshot): ?SeoSitemapEntry
    {
        if ($loc === null || trim($loc) === '') {
            return null;
        }

        return new SeoSitemapEntry(
            loc: $loc,
            lastModifiedAt: $snapshot->modifiedAt,
            alternates: $snapshot->translationUrls,
        );
    }
}
