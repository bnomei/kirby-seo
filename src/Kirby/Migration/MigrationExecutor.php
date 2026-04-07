<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Migration;

use Bnomei\Seo\Kirby\SeoRuntimeBridge;
use Kirby\Cms\App;

final class MigrationExecutor
{
    public function __construct(
        private readonly App $kirby,
    ) {}

    public function apply(MigrationPlan $plan): void
    {
        $this->kirby->impersonate('kirby', function () use ($plan): void {
            foreach ($plan->modelChanges as $change) {
                if ($change->hasWrites() === false) {
                    continue;
                }

                $change->model()->version('latest')->save($change->writes, $change->languageCode, false);
            }

            foreach ($plan->fileChanges as $change) {
                $change->apply();
            }
        });

        SeoRuntimeBridge::resetDocumentCache();
    }
}
