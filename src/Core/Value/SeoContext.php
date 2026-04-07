<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Value;

use Bnomei\Seo\Core\Contract\SeoValueObject;

final readonly class SeoContext implements SeoValueObject
{
    public function __construct(
        public SeoDocumentType $documentType,
        public string $id,
        public string $url,
        public string $language,
        public ?string $languageName = null,
        public ?string $siteUrl = null,
        public ?string $template = null,
        public SeoVisibility $visibility = SeoVisibility::Listed,
        public bool $isHomepage = false,
        public bool $isMultilingual = false,
    ) {}

    public function toArray(): array
    {
        return [
            'documentType' => $this->documentType->value,
            'id' => $this->id,
            'url' => $this->url,
            'language' => $this->language,
            'languageName' => $this->languageName,
            'siteUrl' => $this->siteUrl,
            'template' => $this->template,
            'visibility' => $this->visibility->value,
            'isHomepage' => $this->isHomepage,
            'isMultilingual' => $this->isMultilingual,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
