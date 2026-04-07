<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Migration;

use Kirby\Cms\App;

final class MigrationService
{
    public function __construct(
        private readonly App $kirby,
        private readonly MigrationPlanner $planner,
        private readonly MigrationExecutor $executor,
    ) {}

    public static function for(App $kirby): self
    {
        return new self(kirby: $kirby, planner: new MigrationPlanner($kirby), executor: new MigrationExecutor($kirby));
    }

    public function run(MigrationAdapter $adapter, bool $apply = false): MigrationReport
    {
        $plan = $this->planner->plan($adapter);

        if ($apply === true) {
            $this->executor->apply($plan);
        }

        return MigrationReport::fromPlan($plan, $apply);
    }
}
