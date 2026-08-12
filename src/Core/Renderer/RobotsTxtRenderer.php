<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Renderer;

use Bnomei\Seo\Core\Value\SeoRobotsRuleGroup;
use Bnomei\Seo\Core\Value\SeoRobotsRules;

final class RobotsTxtRenderer
{
    public function render(SeoRobotsRules $rules): string
    {
        $lines = [];

        foreach ($rules->groups as $group) {
            $lines = [...$lines, ...$this->groupLines($group), ''];
        }

        foreach ($rules->sitemapUrls as $sitemapUrl) {
            $lines[] = 'Sitemap: ' . $sitemapUrl;
        }

        return rtrim(implode("\n", $lines)) . "\n";
    }

    /**
     * @return list<string>
     */
    private function groupLines(SeoRobotsRuleGroup $group): array
    {
        $lines = [];

        foreach ($group->comments as $comment) {
            $lines[] = '# ' . trim($comment);
        }

        $lines[] = 'User-agent: ' . $group->userAgent;

        $lines = [...$lines, ...$this->directiveLines($group->directives)];

        foreach ($group->allow as $path) {
            $lines[] = 'Allow: ' . $path;
        }

        foreach ($group->disallow as $path) {
            $lines[] = 'Disallow: ' . $path;
        }

        return $lines;
    }

    /**
     * @param array<string, list<string>> $directives
     * @return list<string>
     */
    private function directiveLines(array $directives): array
    {
        return array_merge(...array_map(
            static fn(string $name, array $values): array => array_map(
                static fn(string $value): string => $name . ': ' . $value,
                $values,
            ),
            array_keys($directives),
            array_values($directives),
        ));
    }
}
