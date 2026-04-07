<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Ai;

interface SeoAiTransport
{
    public function send(SeoAiHttpRequest $request): SeoAiHttpResponse;
}
