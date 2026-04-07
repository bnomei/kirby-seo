<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Ai;

interface SeoAiProviderAdapter
{
    public function buildRequest(SeoAiRequest $request): SeoAiHttpRequest;

    public function parseResponse(SeoAiHttpResponse $response): SeoAiResult;
}
