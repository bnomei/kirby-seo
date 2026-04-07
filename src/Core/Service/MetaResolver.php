<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Service;

use Bnomei\Seo\Core\Value\SeoContentSnapshot;
use Bnomei\Seo\Core\Value\SeoContext;
use Bnomei\Seo\Core\Value\SeoResolvedMeta;
use Bnomei\Seo\Core\Value\SeoResolvedState;
use Bnomei\Seo\Core\Value\SeoRuleSet;

final class MetaResolver
{
    public function __construct(
        private readonly RobotsResolver $robotsResolver = new RobotsResolver(),
    ) {}

    public function resolve(
        SeoContext $context,
        SeoContentSnapshot $snapshot,
        SeoRuleSet $rules,
        SeoResolvedState $state,
    ): SeoResolvedMeta {
        $title = $snapshot->effectiveTitle();

        if ($title === '') {
            $title = $context->id;
        }

        if ($rules->titleSuffix !== null && $rules->titleSuffix !== '') {
            $suffix = trim($rules->titleSuffix);

            if ($suffix !== '' && str_ends_with($title, $suffix) === false) {
                $title .= $rules->titleSeparator . $suffix;
            }
        }

        $description = $snapshot->effectiveDescription($rules->defaultDescription);
        $canonicalUrl = $this->canonicalUrl($context, $snapshot, $rules);
        $alternates = $this->alternates($snapshot);
        $robotsDirectives = $this->robotsResolver->resolve($state->indexable, $rules);

        $metaTags = [];

        foreach ([
            'og:title' => $title,
            'og:description' => $description,
            'og:url' => $canonicalUrl,
            'og:image' => $snapshot->imageUrl,
            'twitter:card' => $snapshot->imageUrl ? 'summary_large_image' : 'summary',
            'twitter:title' => $title,
            'twitter:description' => $description,
        ] as $name => $content) {
            if (is_string($content) === true && $content !== '') {
                $metaTags[$name] = $content;
            }
        }

        $metaTags = [
            ...$metaTags,
            ...$snapshot->metaTags,
            ...$rules->metaTags,
        ];

        return new SeoResolvedMeta(
            title: $title,
            description: $description,
            canonicalUrl: $canonicalUrl,
            alternates: $alternates,
            robotsDirectives: $robotsDirectives,
            metaTags: $metaTags,
            imageUrl: $snapshot->imageUrl,
        );
    }

    private function canonicalUrl(SeoContext $context, SeoContentSnapshot $snapshot, SeoRuleSet $rules): ?string
    {
        $canonical = trim($rules->canonicalUrl ?? $snapshot->canonicalUrl ?? $context->url);

        return $canonical === '' ? null : $canonical;
    }

    /**
     * @return array<string, string>
     */
    private function alternates(SeoContentSnapshot $snapshot): array
    {
        $alternates = array_filter($snapshot->translationUrls, static fn(string $url): bool => trim($url) !== '');

        ksort($alternates);

        return $alternates;
    }
}
