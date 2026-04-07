<?php

declare(strict_types=1);

use Bnomei\Seo\Tests\Support\SnapshotNormalizer;

require_once __DIR__ . '/Support/SnapshotNormalizer.php';
require_once __DIR__ . '/Support/functions.php';

uses()
    ->group('unit')
    ->in('Unit');

uses()
    ->group('integration')
    ->in('Integration');

uses()
    ->group('architecture')
    ->in('Architecture');

expect()->extend('normalized', function (): string {
    /** @var mixed $this->value */
    return SnapshotNormalizer::normalize($this->value);
});
