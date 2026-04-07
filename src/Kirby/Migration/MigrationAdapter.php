<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Migration;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\Site;

interface MigrationAdapter
{
    public function source(): string;

    public function label(): string;

    public function planModel(Page|Site $model, string $languageCode): ?MigrationModelChange;

    /**
     * @return MigrationFileChange[]
     */
    public function planFileChanges(App $kirby): array;

    /**
     * @return string[]
     */
    public function manualFollowUps(App $kirby): array;
}
