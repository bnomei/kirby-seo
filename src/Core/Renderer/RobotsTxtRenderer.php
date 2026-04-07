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

        foreach ($group->allow as $path) {
            $lines[] = 'Allow: ' . $path;
        }

        foreach ($group->disallow as $path) {
            $lines[] = 'Disallow: ' . $path;
        }

        return $lines;
    }
}
