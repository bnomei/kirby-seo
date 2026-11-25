<?php

declare(strict_types=1);

return [
    'url' => getenv('PUBLIC_URL') ?: null,
    'debug' => true,
    'languages' => true,
    'panel' => [
        // 'dev' => true,
    ],
    'thumbs' => [
        'presets' => [
            'seo' => [
                'width' => 1200,
                'height' => 630,
                'crop' => true,
                'quality' => 82,
            ],
        ],
    ],
];
