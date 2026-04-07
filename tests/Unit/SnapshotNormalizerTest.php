<?php

declare(strict_types=1);

use Bnomei\Seo\Tests\Support\SnapshotNormalizer;

it('normalizes arrays into stable snapshot strings', function (): void {
    $normalized = SnapshotNormalizer::normalize([
        'path' => dirname(__DIR__, 2) . '/tests',
        'line' => "alpha\r\nbeta",
    ]);

    expect($normalized)->toContain('<ROOT>/tests');
    expect($normalized)->toContain('alpha\nbeta');
});
