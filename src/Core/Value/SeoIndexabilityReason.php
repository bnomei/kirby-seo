<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Value;

enum SeoIndexabilityReason: string
{
    case Allowed = 'allowed';
    case Draft = 'draft';
    case TemplateExcluded = 'template-excluded';
    case ConfigAllowed = 'config-allowed';
    case ConfigBlocked = 'config-blocked';
    case ManualAllowed = 'manual-allowed';
    case ManualBlocked = 'manual-blocked';
}
