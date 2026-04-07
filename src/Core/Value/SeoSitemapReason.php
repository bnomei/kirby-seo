<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Value;

enum SeoSitemapReason: string
{
    case Included = 'included';
    case ConfigIncluded = 'config-included';
    case ConfigExcluded = 'config-excluded';
    case Draft = 'draft';
    case VisibilityExcluded = 'visibility-excluded';
    case IndexingBlocked = 'indexing-blocked';
    case TemplateExcluded = 'template-excluded';
    case MissingCanonical = 'missing-canonical';
}
