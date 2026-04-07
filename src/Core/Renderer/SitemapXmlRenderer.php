<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Renderer;

use Bnomei\Seo\Core\Value\SeoSitemapEntry;

final class SitemapXmlRenderer
{
    /**
     * @param list<SeoSitemapEntry> $entries
     */
    public function render(array $entries): string
    {
        usort($entries, static fn(SeoSitemapEntry $left, SeoSitemapEntry $right): int => $left->loc <=> $right->loc);

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">',
        ];

        foreach ($entries as $entry) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . $this->escape($entry->loc) . '</loc>';

            if ($entry->lastModifiedAt !== null) {
                $lines[] = '    <lastmod>' . $entry->lastModifiedAt->format(DATE_ATOM) . '</lastmod>';
            }

            $alternates = $entry->alternates;
            ksort($alternates);

            foreach ($alternates as $language => $url) {
                $lines[] =
                    '    <xhtml:link rel="alternate" hreflang="'
                    . $this->escape($language)
                    . '" href="'
                    . $this->escape($url)
                    . '" />';
            }

            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines) . "\n";
    }

    private function escape(string $value): string
    {
        return htmlspecialchars(string: $value, flags: ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, encoding: 'UTF-8');
    }
}
