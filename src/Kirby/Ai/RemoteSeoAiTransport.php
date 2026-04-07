<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Ai;

use Bnomei\Seo\Core\Ai\SeoAiHttpRequest;
use Bnomei\Seo\Core\Ai\SeoAiHttpResponse;
use Bnomei\Seo\Core\Ai\SeoAiTransport;
use Kirby\Http\Remote;

final class RemoteSeoAiTransport implements SeoAiTransport
{
    public function send(SeoAiHttpRequest $request): SeoAiHttpResponse
    {
        $remote = Remote::request($request->url, [
            'method' => $request->method,
            'headers' => $request->headers,
            'data' => is_array($request->body)
                ? json_encode($request->body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                : $request->body,
            'timeout' => $request->timeout,
        ]);

        $body = $remote->content();
        $decoded = is_string($body) ? json_decode(json: $body, associative: true) : null;

        return new SeoAiHttpResponse(
            statusCode: $remote->code() ?? 500,
            headers: self::normalizeHeaders($remote->headers()),
            body: is_array($decoded) ? $decoded : $body,
        );
    }

    /**
     * @return array<string, string>
     */
    private static function normalizeHeaders(mixed $value): array
    {
        if (is_array($value) === false) {
            return [];
        }

        $headers = [];

        foreach ($value as $key => $header) {
            if (is_string($key) === true && is_scalar($header)) {
                $headers[$key] = (string) $header;
            }
        }

        return $headers;
    }
}
