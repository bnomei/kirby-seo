<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Service;

use Bnomei\Seo\Core\Value\SeoContentSnapshot;
use Bnomei\Seo\Core\Value\SeoContext;
use Bnomei\Seo\Core\Value\SeoIndexabilityReason;
use Bnomei\Seo\Core\Value\SeoRuleSet;
use Bnomei\Seo\Core\Value\SeoVisibility;

final class IndexabilityResolver
{
    /**
     * @return array{indexable: bool, reason: SeoIndexabilityReason}
     */
    public function resolve(SeoContext $context, SeoContentSnapshot $snapshot, SeoRuleSet $rules): array
    {
        if ($context->visibility === SeoVisibility::Draft) {
            return [
                'indexable' => false,
                'reason' => SeoIndexabilityReason::Draft,
            ];
        }

        if ($snapshot->searchAllowed !== null) {
            return [
                'indexable' => $snapshot->searchAllowed,
                'reason' => $snapshot->searchAllowed
                    ? SeoIndexabilityReason::ManualAllowed
                    : SeoIndexabilityReason::ManualBlocked,
            ];
        }

        if ($rules->indexable !== null) {
            return [
                'indexable' => $rules->indexable,
                'reason' => $rules->indexable
                    ? SeoIndexabilityReason::ConfigAllowed
                    : SeoIndexabilityReason::ConfigBlocked,
            ];
        }

        if ($rules->templateExcludedFromIndex === true) {
            return [
                'indexable' => false,
                'reason' => SeoIndexabilityReason::TemplateExcluded,
            ];
        }

        return [
            'indexable' => true,
            'reason' => SeoIndexabilityReason::Allowed,
        ];
    }
}
