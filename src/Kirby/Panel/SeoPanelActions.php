<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Panel;

use Bnomei\Seo\Kirby\Ai\SeoAiService;
use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Panel\Panel;

final class SeoPanelActions
{
    public static function dropdown(Page|Site $model): array
    {
        return SeoPanelActionDropdown::for($model);
    }

    public static function submit(Page|Site $model, string $action): array
    {
        SeoPanelActionAccess::assertAllowed($model);

        $action = self::normalizeAction($model, $action);

        $kirby = App::instance();
        $language = $kirby->languageCode() ?? $kirby->defaultLanguage()?->code() ?? 'en';
        $service = SeoAiService::for($kirby);

        match ($action) {
            'generate' => $model instanceof Page
                ? $service->generatePage($model, $language)
                : $service->generateSite($model, $language),
            'lock' => $service->setAiState($model, 'locked', $language),
            'unlock' => $service->setAiState($model, 'auto', $language),
            'hide' => $service->setSearchIndexOverride($model, false, $language),
            'allow' => $service->setSearchIndexOverride($model, true, $language),
            default => $model,
        };

        return [
            'event' => $model instanceof Page ? 'page.update' : 'site.update',
        ];
    }

    public static function perform(Page|Site $model, string $action): never
    {
        self::submit($model, $action);
        Panel::go(self::redirectPath($model));
    }

    private static function redirectPath(Page|Site $model): string
    {
        return $model->panel()->url(true);
    }

    private static function normalizeAction(Page|Site $model, string $action): string
    {
        if ($action === 'refresh') {
            return 'generate';
        }

        if ($action !== 'toggleAiState') {
            return $action;
        }

        return self::aiState($model) === 'locked' ? 'unlock' : 'lock';
    }

    private static function aiState(Page|Site $model): string
    {
        $currentState = $model->content()->get('seoAiState')->value();
        $currentState = is_string($currentState) ? trim($currentState) : '';

        return $currentState === 'locked' ? 'locked' : 'auto';
    }
}
