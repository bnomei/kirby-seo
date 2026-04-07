<?php

declare(strict_types=1);

$candidate = $page ?? $site ?? $model ?? kirby()->page() ?? null;

if (is_object($candidate) === false || is_callable([$candidate, 'seoHeadTags']) === false) {
    return;
}

$headTags = $candidate->seoHeadTags();

if (is_string($headTags) === false || $headTags === '') {
    return;
}

echo $headTags;
