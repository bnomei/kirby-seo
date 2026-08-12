<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Value;

use Bnomei\Seo\Core\Contract\SeoValueObject;

final readonly class SeoRobotsRuleGroup implements SeoValueObject
{
    /**
     * @var array<string, list<string>>
     */
    public array $directives;

    /**
     * @param list<string> $allow
     * @param list<string> $disallow
     * @param list<string> $comments
     * @param array<string, string|list<string>> $directives
     */
    public function __construct(
        public string $userAgent,
        public array $allow = [],
        public array $disallow = [],
        public array $comments = [],
        array $directives = [],
    ) {
        $this->directives = self::normalizeDirectives($directives);
    }

    public function toArray(): array
    {
        return [
            'userAgent' => $this->userAgent,
            'allow' => $this->allow,
            'disallow' => $this->disallow,
            'comments' => $this->comments,
            'directives' => $this->directives,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param array<string, string|list<string>> $directives
     * @return array<string, list<string>>
     */
    private static function normalizeDirectives(array $directives): array
    {
        $normalized = [];

        foreach ($directives as $name => $values) {
            $name = self::withoutLineBreaks($name);

            if ($name === '') {
                continue;
            }

            $values = is_array($values) ? $values : [$values];
            $normalized[$name] = array_map(self::withoutLineBreaks(...), array_values($values));
        }

        return $normalized;
    }

    private static function withoutLineBreaks(string $value): string
    {
        return trim(preg_replace(pattern: '/[\r\n]+/', replacement: ' ', subject: $value) ?? $value);
    }
}
