<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Ai;

use Bnomei\Seo\Core\Ai\SeoAiGenerator;
use Bnomei\Seo\Core\Ai\SeoAiProviderAdapter;
use Bnomei\Seo\Core\Ai\SeoAiRequest;
use Bnomei\Seo\Core\Ai\SeoAiRequestBuilder;
use Bnomei\Seo\Core\Ai\SeoAiResult;
use Bnomei\Seo\Core\Ai\SeoAiTransport;
use Bnomei\Seo\Core\Ai\SourceHashBuilder;
use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\Site;

final class SeoAiService
{
    private const OWNED_FIELDS = [
        'seoTitle',
        'seoDescription',
        'seoAiState',
        'seoAiSourceHash',
        'seoAiGeneratedAt',
        'seoAiPromptVersion',
        'seoAiLanguage',
        'seoIndex',
    ];

    public function __construct(
        private readonly App $kirby,
        private readonly SeoAiSnapshotFactory $snapshotFactory = new SeoAiSnapshotFactory(),
        private readonly SeoAiRequestBuilder $requestBuilder = new SeoAiRequestBuilder(),
        private readonly SourceHashBuilder $sourceHashBuilder = new SourceHashBuilder(),
    ) {}

    public static function for(App $kirby): self
    {
        return new self($kirby);
    }

    public function generatePage(Page $page, ?string $languageCode = null): Page
    {
        $languageCode ??= $this->languageCode();
        $request = $this->buildRequest('page-meta', $page, $languageCode);
        $result = $this->generate($request, $page, $languageCode);

        /** @var Page */
        return $this->persist($page, $languageCode, $request, $result);
    }

    public function generateSite(Site $site, ?string $languageCode = null): Site
    {
        $languageCode ??= $this->languageCode();
        $request = $this->buildRequest('site-defaults', $site, $languageCode);
        $result = $this->generate($request, $site, $languageCode);

        /** @var Site */
        return $this->persist($site, $languageCode, $request, $result);
    }

    public function isStale(Page|Site $model, ?string $languageCode = null): bool
    {
        $languageCode ??= $this->languageCode();
        $snapshot = $this->snapshotFactory->build($model, $languageCode);

        return $snapshot->aiSourceHash !== $this->sourceHashBuilder->build($snapshot);
    }

    public function handlePageUpdate(Page $newPage, Page $oldPage): void
    {
        $this->handleModelUpdate($newPage, $oldPage);
    }

    public function handleSiteUpdate(Site $newSite, Site $oldSite): void
    {
        $this->handleModelUpdate($newSite, $oldSite);
    }

    public function setAiState(Page|Site $model, string $state, ?string $languageCode = null): Page|Site
    {
        $languageCode ??= $this->languageCode();

        return $this->guardedUpdate($model, $languageCode, [
            'seoAiState' => $state,
        ]);
    }

    public function setSearchIndexOverride(Page|Site $model, bool $allow, ?string $languageCode = null): Page|Site
    {
        $languageCode ??= $this->languageCode();

        return $this->guardedUpdate($model, $languageCode, [
            'seoIndex' => $allow ? '1' : '0',
        ]);
    }

    private function buildRequest(string $operation, Page|Site $model, string $languageCode): SeoAiRequest
    {
        $snapshot = $this->snapshotFactory->build($model, $languageCode);

        return $this->requestBuilder->build(
            operation: $operation,
            snapshot: $snapshot,
            systemPromptTemplate: (string) $this->option('ai.prompts.system', $this->defaultSystemPrompt()),
            userPromptTemplate: (string) $this->option(
                $operation === 'site-defaults' ? 'ai.prompts.site' : 'ai.prompts.page',
                $operation === 'site-defaults' ? $this->defaultSitePrompt() : $this->defaultPagePrompt(),
            ),
            variables: [
                'promptVersion' => $this->promptVersion(),
            ],
            settings: [
                'provider' => $this->provider(),
                'model' => $this->model(),
            ],
        );
    }

    private function generate(SeoAiRequest $request, Page|Site $model, string $languageCode): SeoAiResult
    {
        $override = $this->option('ai.generate');

        if ($override instanceof \Closure) {
            return SeoAiResultNormalizer::normalize($override($request, $model, $languageCode, $this->kirby));
        }

        $adapter = $this->adapter();
        $transport = $this->transport();

        return (new SeoAiGenerator($adapter, $transport))->generate($request);
    }

    private function persist(
        Page|Site $model,
        string $languageCode,
        SeoAiRequest $request,
        SeoAiResult $result,
    ): Page|Site {
        return $this->guardedUpdate($model, $languageCode, [
            'seoTitle' => $result->title,
            'seoDescription' => $result->description,
            'seoAiState' => $this->aiState($model, $languageCode),
            'seoAiSourceHash' => $request->sourceHash,
            'seoAiGeneratedAt' => date(DATE_ATOM),
            'seoAiPromptVersion' => $this->promptVersion(),
            'seoAiLanguage' => $languageCode,
        ]);
    }

    private function guardedUpdate(Page|Site $model, string $languageCode, array $values): Page|Site
    {
        $key = $this->guardKey($model, $languageCode);

        /** @var Page|Site */
        return SeoAiHookGuard::run($key, fn() => $this->kirby->impersonate('kirby', function () use (
            $model,
            $languageCode,
            $values,
        ) {
            $model->version('latest')->save($values, $languageCode, false);

            return $this->refreshModel($model);
        }));
    }

    private function handleModelUpdate(Page|Site $newModel, Page|Site $oldModel): void
    {
        $languageCode = $this->languageCode();
        $key = $this->guardKey($newModel, $languageCode);
        $newModel = $this->refreshModel($newModel);

        if (SeoAiHookGuard::active($key) === true) {
            return;
        }

        if ($this->changedOnlyOwnedFields($oldModel, $newModel, $languageCode) === true) {
            return;
        }

        if (
            $this->aiState($newModel, $languageCode) !== 'auto'
            || $this->aiState($oldModel, $languageCode) !== 'auto'
        ) {
            return;
        }

        if ($this->isStale($newModel, $languageCode) === false) {
            return;
        }

        try {
            if ($newModel instanceof Page) {
                $this->generatePage($newModel, $languageCode);
            } else {
                $this->generateSite($newModel, $languageCode);
            }
        } catch (\Throwable $error) {
            // Auto refresh is best-effort only.
            unset($error);
        }
    }

    private function adapter(): SeoAiProviderAdapter
    {
        $provider = $this->provider();
        $config = $this->option('ai.providers.' . $provider, []);

        if ($config instanceof \Closure) {
            $config = $config($this->kirby);
        }

        $config = is_array($config) ? $config : [];
        $apiKey = (string) ($config['apiKey'] ?? $this->option('ai.apiKey', ''));
        $model = (string) ($config['model'] ?? $this->model());
        $timeout = (int) ($config['timeout'] ?? $this->option('ai.timeout', 30));

        return match ($provider) {
            'anthropic' => new AnthropicSeoAiProviderAdapter($apiKey, $model, timeout: $timeout),
            'gemini' => new GeminiSeoAiProviderAdapter($apiKey, $model, timeout: $timeout),
            default => new OpenAiSeoAiProviderAdapter($apiKey, $model, timeout: $timeout),
        };
    }

    private function transport(): SeoAiTransport
    {
        $transport = $this->option('ai.transport');

        if ($transport instanceof \Closure) {
            return new CallbackSeoAiTransport($transport);
        }

        return new RemoteSeoAiTransport();
    }

    private function aiState(Page|Site $model, string $languageCode): string
    {
        $value = $model->content($languageCode)->get('seoAiState')->value();
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : 'auto';
    }

    private function changedOnlyOwnedFields(Page|Site $oldModel, Page|Site $newModel, string $languageCode): bool
    {
        $old = $oldModel->content($languageCode)->toArray();
        $new = $newModel->content($languageCode)->toArray();
        $changed = [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $field) {
            if (($old[$field] ?? null) !== ($new[$field] ?? null)) {
                $changed[] = $field;
            }
        }

        return $changed !== [] && array_diff($changed, self::OWNED_FIELDS) === [];
    }

    private function guardKey(Page|Site $model, string $languageCode): string
    {
        return ($model instanceof Page ? $model->id() : 'site') . ':' . $languageCode;
    }

    private function refreshModel(Page|Site $model): Page|Site
    {
        if ($model instanceof Page) {
            return $this->kirby->page($model->id()) ?? $model;
        }

        return $this->kirby->site();
    }

    private function provider(): string
    {
        $provider = $this->option('ai.provider', 'openai');

        if ($provider instanceof \Closure) {
            $provider = $provider($this->kirby);
        }

        return is_string($provider) && $provider !== '' ? $provider : 'openai';
    }

    private function model(): string
    {
        $model = $this->option('ai.model', match ($this->provider()) {
            'anthropic' => 'claude-sonnet-4-5',
            'gemini' => 'gemini-2.5-flash',
            default => 'gpt-5-mini',
        });

        if ($model instanceof \Closure) {
            $model = $model($this->kirby, $this->provider());
        }

        return is_string($model) && $model !== '' ? $model : 'gpt-5-mini';
    }

    private function promptVersion(): string
    {
        $version = $this->option('ai.promptVersion', 'v1');

        return is_string($version) && $version !== '' ? $version : 'v1';
    }

    private function defaultSystemPrompt(): string
    {
        return 'You write localized SEO metadata for Kirby CMS content. Return JSON with exactly two string keys: title and description.';
    }

    private function defaultPagePrompt(): string
    {
        return <<<TEXT
            Language: {{languageName}} ({{languageCode}})
            URL: {{url}}
            Title: {{title}}
            Excerpt: {{excerpt}}
            Content:
            {{content}}

            Write an SEO title and meta description for this page. Keep the language consistent with the target language.
            TEXT;
    }

    private function defaultSitePrompt(): string
    {
        return <<<TEXT
            Language: {{languageName}} ({{languageCode}})
            URL: {{url}}
            Title: {{title}}
            Excerpt: {{excerpt}}
            Content:
            {{content}}

            Write SEO title and meta description defaults for this site. Keep the language consistent with the target language.
            TEXT;
    }

    private function option(string $key, mixed $default = null): mixed
    {
        return $this->kirby->option('bnomei.seo.' . $key, $default);
    }

    private function languageCode(): string
    {
        return $this->kirby->language()?->code() ?? $this->kirby->defaultLanguage()?->code() ?? 'en';
    }
}
