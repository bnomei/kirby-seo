<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Renderer;

use Bnomei\Seo\Core\Value\SeoResolvedDocument;

final class HeadTagRenderer
{
    public function render(SeoResolvedDocument $document): string
    {
        $lines = [
            '<title>' . $this->escape($document->meta->title) . '</title>',
            '<meta name="description" content="' . $this->escape($document->meta->description) . '">',
            '<meta name="robots" content="' . $this->escape($document->meta->robots()) . '">',
        ];

        if ($document->meta->canonicalUrl !== null) {
            $lines[] = '<link rel="canonical" href="' . $this->escape($document->meta->canonicalUrl) . '">';
        }

        foreach ($document->meta->alternates as $language => $url) {
            $lines[] =
                '<link rel="alternate" hreflang="' . $this->escape($language) . '" href="' . $this->escape($url) . '">';
        }

        foreach ($document->meta->metaTags as $name => $content) {
            $attribute = str_starts_with($name, 'og:') ? 'property' : 'name';
            $lines[] =
                '<meta ' . $attribute . '="' . $this->escape($name) . '" content="' . $this->escape($content) . '">';
        }

        return implode("\n", $lines) . "\n";
    }

    private function escape(string $value): string
    {
        return htmlspecialchars(string: $value, flags: ENT_QUOTES | ENT_SUBSTITUTE, encoding: 'UTF-8');
    }
}
