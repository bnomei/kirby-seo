<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Ai;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\Site;

final class SeoAiHooks
{
    public static function pageUpdated(Page $newPage, Page $oldPage): void
    {
        SeoAiService::for(App::instance())->handlePageUpdate($newPage, $oldPage);
    }

    public static function siteUpdated(Site $newSite, Site $oldSite): void
    {
        SeoAiService::for(App::instance())->handleSiteUpdate($newSite, $oldSite);
    }
}
