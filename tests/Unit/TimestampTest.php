<?php

declare(strict_types=1);

use Bnomei\Seo\Core\Support\Timestamp;

it('converts positive unix timestamps to utc datetimes', function (): void {
    $datetime = Timestamp::toDateTimeImmutable(1_717_200_000);

    expect($datetime)->toBeInstanceOf(DateTimeImmutable::class);
    expect($datetime?->getTimestamp())->toBe(1_717_200_000);
    expect($datetime?->format('Y-m-d\TH:i:sP'))->toBe('2024-06-01T00:00:00+00:00');
});

it('accepts numeric string timestamps from formatted page modified values', function (): void {
    $datetime = Timestamp::toDateTimeImmutable('1717200000');

    expect($datetime)->toBeInstanceOf(DateTimeImmutable::class);
    expect($datetime?->getTimestamp())->toBe(1_717_200_000);
});

it('treats missing or invalid page mtimes as no lastmod', function (int|string|false|null $timestamp): void {
    expect(Timestamp::toDateTimeImmutable($timestamp))->toBeNull();
})->with([
    'false' => false,
    'null' => null,
    'empty string' => '',
    'zero' => 0,
    'negative' => -1,
    'zero string' => '0',
    'non-numeric string' => 'not-a-timestamp',
]);
