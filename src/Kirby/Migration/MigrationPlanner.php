<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Migration;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\Site;

final class MigrationPlanner
{
    public function __construct(
        private readonly App $kirby,
    ) {}

    public function plan(MigrationAdapter $adapter): MigrationPlan
    {
        $changes = [];

        foreach ($this->models() as $model) {
            foreach ($this->languagesFor($model) as $languageCode) {
                $change = $adapter->planModel($model, $languageCode);

                if ($change !== null) {
                    $changes[] = $change;
                }
            }
        }

        return new MigrationPlan(
            source: $adapter->source(),
            label: $adapter->label(),
            modelChanges: $changes,
            fileChanges: $adapter->planFileChanges($this->kirby),
            followUps: $adapter->manualFollowUps($this->kirby),
        );
    }

    /**
     * @return iterable<Page|Site>
     */
    private function models(): iterable
    {
        yield $this->kirby->site();

        foreach ($this->kirby->site()->index(true) as $page) {
            if ($page instanceof Page) {
                yield $page;
            }
        }
    }

    /**
     * @return string[]
     */
    private function languagesFor(Page|Site $model): array
    {
        if ($this->kirby->multilang() === false) {
            return [$this->kirby->defaultLanguage()?->code() ?? 'en'];
        }

        $codes = [];

        foreach ($this->kirby->languages() as $language) {
            $code = $language->code();

            if ($model instanceof Site || $model->translation($code)->exists() === true) {
                $codes[] = $code;
            }
        }

        return $codes !== [] ? $codes : [$this->kirby->defaultLanguage()?->code() ?? 'en'];
    }
}
