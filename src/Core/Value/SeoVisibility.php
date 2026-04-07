<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Value;

enum SeoVisibility: string
{
    case Draft = 'draft';
    case Unlisted = 'unlisted';
    case Listed = 'listed';
}
