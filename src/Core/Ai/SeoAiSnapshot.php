<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Ai;

use Bnomei\Seo\Core\Contract\SeoValueObject;
use DateTimeImmutable;
use JsonSerializable;

/**
 * @phpstan-type SeoVisibility 'draft'|'unlisted'|'listed'
 * @phpstan-type SeoAiState 'auto'|'locked'
 * @phpstan-type SeoIndexOverride 'default'|'allow'|'hide'
 * @phpstan-type SeoAiSnapshotInput array{
 *     kind: string,
 *     id: string,
 *     slug: string,
 *     uri: string,
 *     template: string,
 *     url: string,
 *     title: string,
 *     excerpt: string,
 *     text: string,
 *     fields: array<string, scalar|array|null>,
 *     languageCode: string,
 *     languageName: string,
 *     visibility: string,
 *     updatedAt?: ?DateTimeImmutable,
 *     alternateUrls?: array<string, string>,
 *     aiTitle?: ?string,
 *     aiDescription?: ?string,
 *     aiState?: string,
 *     aiSourceHash?: ?string,
 *     aiGeneratedAt?: ?DateTimeImmutable,
 *     aiPromptVersion?: ?string,
 *     aiLanguage?: ?string,
 *     seoIndexOverride?: string,
 *     canonicalUrl?: ?string
 * }
 */
final readonly class SeoAiSnapshot implements JsonSerializable, SeoValueObject
{
    public string $kind;
    public string $id;
    public string $slug;
    public string $uri;
    public string $template;
    public string $url;
    public string $title;
    public string $excerpt;
    public string $text;
    /**
     * @var array<string, scalar|array|null>
     */
    public array $fields;
    public string $languageCode;
    public string $languageName;
    public string $visibility;
    public ?DateTimeImmutable $updatedAt;
    /**
     * @var array<string, string>
     */
    public array $alternateUrls;
    public ?string $aiTitle;
    public ?string $aiDescription;
    public string $aiState;
    public ?string $aiSourceHash;
    public ?DateTimeImmutable $aiGeneratedAt;
    public ?string $aiPromptVersion;
    public ?string $aiLanguage;
    public string $seoIndexOverride;
    public ?string $canonicalUrl;

    /**
     * @param SeoAiSnapshotInput $data
     */
    private function __construct(array $data)
    {
        $this->kind = $data['kind'];
        $this->id = $data['id'];
        $this->slug = $data['slug'];
        $this->uri = $data['uri'];
        $this->template = $data['template'];
        $this->url = $data['url'];
        $this->title = $data['title'];
        $this->excerpt = $data['excerpt'];
        $this->text = $data['text'];
        $this->fields = $data['fields'];
        $this->languageCode = $data['languageCode'];
        $this->languageName = $data['languageName'];
        $this->visibility = $data['visibility'];
        $this->updatedAt = $data['updatedAt'] ?? null;
        $this->alternateUrls = $data['alternateUrls'] ?? [];
        $this->aiTitle = $data['aiTitle'] ?? null;
        $this->aiDescription = $data['aiDescription'] ?? null;
        $this->aiState = $data['aiState'] ?? 'auto';
        $this->aiSourceHash = $data['aiSourceHash'] ?? null;
        $this->aiGeneratedAt = $data['aiGeneratedAt'] ?? null;
        $this->aiPromptVersion = $data['aiPromptVersion'] ?? null;
        $this->aiLanguage = $data['aiLanguage'] ?? null;
        $this->seoIndexOverride = $data['seoIndexOverride'] ?? 'default';
        $this->canonicalUrl = $data['canonicalUrl'] ?? null;
    }

    /**
     * @param SeoAiSnapshotInput $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function isDraft(): bool
    {
        return $this->visibility === 'draft';
    }

    public function isListed(): bool
    {
        return $this->visibility === 'listed';
    }

    public function field(string $key, mixed $default = null): mixed
    {
        return $this->fields[$key] ?? $default;
    }

    public function lastModified(): ?string
    {
        return $this->updatedAt?->format(DATE_ATOM);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'id' => $this->id,
            'slug' => $this->slug,
            'uri' => $this->uri,
            'template' => $this->template,
            'url' => $this->url,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'text' => $this->text,
            'fields' => $this->fields,
            'languageCode' => $this->languageCode,
            'languageName' => $this->languageName,
            'visibility' => $this->visibility,
            'updatedAt' => $this->lastModified(),
            'alternateUrls' => $this->alternateUrls,
            'aiTitle' => $this->aiTitle,
            'aiDescription' => $this->aiDescription,
            'aiState' => $this->aiState,
            'aiSourceHash' => $this->aiSourceHash,
            'aiGeneratedAt' => $this->aiGeneratedAt?->format(DATE_ATOM),
            'aiPromptVersion' => $this->aiPromptVersion,
            'aiLanguage' => $this->aiLanguage,
            'seoIndexOverride' => $this->seoIndexOverride,
            'canonicalUrl' => $this->canonicalUrl,
        ];
    }
}
