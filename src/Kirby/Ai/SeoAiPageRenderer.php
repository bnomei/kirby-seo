<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Ai;

use Kirby\Cms\App;
use Kirby\Cms\Page;

final class SeoAiPageRenderer
{
    public function render(Page $page, string $languageCode): string
    {
        $kirby = $page->kirby();
        $site = $kirby->site();
        $previousPage = $site->page();
        $previousLanguageCode = $kirby->language()?->code();
        $html = '';

        try {
            $site->visit($page, $languageCode);

            $html = trim($page->render());
        } catch (\Throwable) {
        } finally {
            if ($previousPage instanceof Page) {
                $site->visit($previousPage, $previousLanguageCode);
            } else {
                $this->restoreLanguage($kirby, $previousLanguageCode);
            }
        }

        return $html;
    }

    private function restoreLanguage(App $kirby, ?string $languageCode): void
    {
        $kirby->setCurrentLanguage($languageCode);

        if ($languageCode === null) {
            return;
        }

        $kirby->setCurrentTranslation($languageCode);
    }
}
