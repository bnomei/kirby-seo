<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Value;

enum SeoDocumentType: string
{
    case Page = 'page';
    case Site = 'site';
}
