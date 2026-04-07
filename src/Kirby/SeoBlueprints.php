<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby;

final class SeoBlueprints
{
    /**
     * @return array<string, callable>
     */
    public static function extensions(): array
    {
        return [
            'tabs/seo/page' => static fn(): array => self::pageTab(),
            'tabs/seo/site' => static fn(): array => self::siteTab(),
            'sections/seo/serp-preview/page' => static fn(): array => self::pageSerpPreviewSection(),
            'sections/seo/serp-preview/site' => static fn(): array => self::siteSerpPreviewSection(),
            'seo/page' => static fn(): array => self::pageTab(),
            'seo/site' => static fn(): array => self::siteTab(),
            'tabs/meta/page' => static fn(): array => self::pageTab(),
            'tabs/meta/site' => static fn(): array => self::siteTab(),
            'seo' => static fn(): array => self::genericTab(),
        ];
    }

    private static function pageTab(): array
    {
        return self::tab(imageQuery: 'page.images');
    }

    private static function siteTab(): array
    {
        return self::tab(imageQuery: 'site.images');
    }

    private static function genericTab(): array
    {
        return self::tab(imageQuery: null);
    }

    private static function pageSerpPreviewSection(): array
    {
        return self::serpPreviewSection(
            defaultTitle: '{{ page.title.value }} – {{ site.title.value }}',
            defaultDescription: '{{ page.text }}',
        );
    }

    private static function siteSerpPreviewSection(): array
    {
        return self::serpPreviewSection(
            defaultTitle: '{{ site.title.value }}',
            defaultDescription: '{{ site.description.value }}',
        );
    }

    private static function tab(?string $imageQuery): array
    {
        $imageField = [
            'type' => 'files',
            'label' => 'bnomei.seo.field.image.label',
            'help' => 'bnomei.seo.field.image.help',
            'max' => 1,
            'multiple' => false,
            'uploads' => false,
        ];

        if ($imageQuery !== null) {
            $imageField['query'] = $imageQuery;
        }

        return [
            'label' => 'bnomei.seo.tab.label',
            'icon' => 'seo',
            'columns' => [
                'main' => [
                    'width' => '2/3',
                    'fields' => [
                        SeoFields::TITLE => [
                            'type' => 'text',
                            'label' => 'SEO title',
                            'width' => '1/1',
                        ],
                        SeoFields::DESCRIPTION => [
                            'type' => 'textarea',
                            'label' => 'SEO description',
                            'buttons' => false,
                            'maxlength' => 320,
                        ],
                        SeoFields::CANONICAL => [
                            'type' => 'url',
                            'label' => 'Canonical URL',
                            'width' => '1/1',
                        ],
                        SeoFields::IMAGE => $imageField,
                    ],
                ],
                'sidebar' => [
                    'width' => '1/3',
                    'fields' => [
                        SeoFields::INDEX => [
                            'type' => 'toggle',
                            'label' => 'bnomei.seo.status.index',
                            'text' => [
                                'Blocked',
                                'Allowed',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private static function serpPreviewSection(string $defaultTitle, string $defaultDescription): array
    {
        return [
            'type' => 'serp-preview',
            'label' => 'bnomei.seo.section.preview.headline',
            'siteTitle' => '{{ site.title.value }}',
            'siteUrl' => '{{ kirby.url }}',
            'titleContentKey' => SeoFields::TITLE,
            'defaultTitle' => $defaultTitle,
            'descriptionContentKey' => SeoFields::DESCRIPTION,
            'defaultDescription' => $defaultDescription,
        ];
    }
}
