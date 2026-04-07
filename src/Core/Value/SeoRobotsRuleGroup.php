<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Value;

use Bnomei\Seo\Core\Contract\SeoValueObject;

final readonly class SeoRobotsRuleGroup implements SeoValueObject
{
    /**
     * @param list<string> $allow
     * @param list<string> $disallow
     * @param list<string> $comments
     */
    public function __construct(
        public string $userAgent,
        public array $allow = [],
        public array $disallow = [],
        public array $comments = [],
    ) {}

    public function toArray(): array
    {
        return [
            'userAgent' => $this->userAgent,
            'allow' => $this->allow,
            'disallow' => $this->disallow,
            'comments' => $this->comments,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
