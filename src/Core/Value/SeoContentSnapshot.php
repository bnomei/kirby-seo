<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Value;

use Bnomei\Seo\Core\Contract\SeoValueObject;
use DateTimeImmutable;

final readonly class SeoContentSnapshot implements SeoValueObject
{
    /**
     * @param array<string, string> $translationUrls
     * @param array<string, string> $metaTags
     */
    public function __construct(
        public string $title = '',
        public ?string $seoTitle = null,
        public ?string $description = null,
        public ?string $seoDescription = null,
        public ?string $excerpt = null,
        public ?string $imageUrl = null,
        public ?string $canonicalUrl = null,
        public array $translationUrls = [],
        public ?DateTimeImmutable $modifiedAt = null,
        public ?bool $searchAllowed = null,
        public array $metaTags = [],
    ) {}

    public function effectiveTitle(): string
    {
        return trim($this->seoTitle !== null && $this->seoTitle !== '' ? $this->seoTitle : $this->title);
    }

    public function effectiveDescription(?string $fallback = null): string
    {
        $description = $this->seoDescription;

        if ($description === null || $description === '') {
            $description = $this->description;
        }

        if ($description === null || $description === '') {
            $description = $this->excerpt;
        }

        if ($description === null || $description === '') {
            $description = $fallback;
        }

        return trim((string) ($description ?? ''));
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'seoTitle' => $this->seoTitle,
            'description' => $this->description,
            'seoDescription' => $this->seoDescription,
            'excerpt' => $this->excerpt,
            'imageUrl' => $this->imageUrl,
            'canonicalUrl' => $this->canonicalUrl,
            'translationUrls' => $this->translationUrls,
            'modifiedAt' => $this->modifiedAt?->format(DATE_ATOM),
            'searchAllowed' => $this->searchAllowed,
            'metaTags' => $this->metaTags,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
