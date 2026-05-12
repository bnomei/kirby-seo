<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Panel;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Exception\AuthException;
use Kirby\Exception\PermissionException;

final class SeoPanelActionAccess
{
    public static function assertAllowed(Page|Site $model): void
    {
        if (App::instance()->auth()->csrf() === false) {
            throw new AuthException(message: 'Unauthenticated');
        }

        if (self::canUpdate($model) === true) {
            return;
        }

        throw new PermissionException(
            key: $model instanceof Page ? 'page.update.permission' : 'site.update.permission',
        );
    }

    public static function canUpdate(Page|Site $model): bool
    {
        return $model->permissions()->can('update') === true;
    }

    public static function link(Page|Site $model, string $action): string
    {
        $path = $model instanceof Page
            ? 'seo/page/' . $model->id() . '/action/' . $action
            : 'seo/site/action/' . $action;

        return $path . '?' . http_build_query([
            'csrf' => App::instance()->auth()->csrfFromSession(),
        ]);
    }
}
