<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Service;

use Bnomei\Seo\Core\Value\SeoRuleSet;

final class RobotsResolver
{
    /**
     * @return list<string>
     */
    public function resolve(bool $indexable, SeoRuleSet $rules): array
    {
        $directives = $indexable ? $rules->robotsWhenIndexable : $rules->robotsWhenBlocked;

        $directives = array_values(array_unique(array_filter(array_map(static fn(string $directive): string => trim(
            $directive,
        ), $directives))));

        return $directives === [] ? ['index', 'follow'] : $directives;
    }
}
