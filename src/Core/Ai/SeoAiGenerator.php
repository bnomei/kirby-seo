<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Ai;

final class SeoAiGenerator
{
    public function __construct(
        private readonly SeoAiProviderAdapter $adapter,
        private readonly SeoAiTransport $transport,
    ) {}

    public function generate(SeoAiRequest $request): SeoAiResult
    {
        $httpRequest = $this->adapter->buildRequest($request);
        $httpResponse = $this->transport->send($httpRequest);

        return $this->adapter->parseResponse($httpResponse);
    }
}
