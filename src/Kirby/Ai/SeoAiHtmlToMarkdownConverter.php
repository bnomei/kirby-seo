<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Ai;

use DOMDocument;
use League\HTMLToMarkdown\HtmlConverter;

final class SeoAiHtmlToMarkdownConverter
{
    private const REMOVE_NODES = 'script style noscript template svg img picture source iframe canvas video audio object embed form input button textarea select option meta link';

    public function __construct(
        private readonly ?HtmlConverter $converter = null,
    ) {}

    public function convert(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        return $this->normalizeMarkdown($this->converter()->convert($this->bodyHtml($html)));
    }

    private function bodyHtml(string $html): string
    {
        $internalErrors = libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $main = $dom->getElementsByTagName('main')->item(0);

        if ($main !== null) {
            $content = '';

            foreach ($main->childNodes as $child) {
                $content .= $dom->saveHTML($child) ?? '';
            }

            return trim($content);
        }

        $body = $dom->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return $html;
        }

        $content = '';

        foreach ($body->childNodes as $child) {
            $content .= $dom->saveHTML($child) ?? '';
        }

        return trim($content);
    }

    private function converter(): HtmlConverter
    {
        return (
            $this->converter ?? new HtmlConverter([
                'bold_style' => '**',
                'hard_break' => true,
                'header_style' => 'atx',
                'italic_style' => '*',
                'remove_nodes' => self::REMOVE_NODES,
                'strip_placeholder_links' => true,
                'strip_tags' => true,
                'suppress_errors' => true,
            ])
        );
    }

    private function normalizeMarkdown(string $markdown): string
    {
        $markdown = str_replace(search: "\r\n", replace: "\n", subject: $markdown);
        $markdown = preg_replace(pattern: '/^[ \t]+$/m', replacement: '', subject: $markdown) ?? $markdown;
        $markdown = preg_replace(pattern: '/!\[[^\]]*]\([^)\n]*\)/', replacement: '', subject: $markdown) ?? $markdown;
        $markdown = preg_replace(pattern: "/\n{3,}/", replacement: "\n\n", subject: $markdown) ?? $markdown;

        return trim($markdown);
    }
}
