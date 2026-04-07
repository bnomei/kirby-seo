<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Value;

use Bnomei\Seo\Core\Contract\SeoValueObject;

final readonly class SeoResolvedMeta implements SeoValueObject
{
    /**
     * @param array<string, string> $alternates
     * @param list<string> $robotsDirectives
     * @param array<string, string> $metaTags
     */
    public function __construct(
        public string $title,
        public string $description,
        public ?string $canonicalUrl,
        public array $alternates = [],
        public array $robotsDirectives = [],
        public array $metaTags = [],
        public ?string $imageUrl = null,
    ) {}

    public function robots(): string
    {
        return implode(', ', $this->robotsDirectives);
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonicalUrl' => $this->canonicalUrl,
            'alternates' => $this->alternates,
            'robotsDirectives' => $this->robotsDirectives,
            'metaTags' => $this->metaTags,
            'imageUrl' => $this->imageUrl,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
