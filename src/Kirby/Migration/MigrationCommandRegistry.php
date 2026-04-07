<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Migration;

use Kirby\Cms\App;

final class MigrationCommandRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function commands(): array
    {
        return [
            'bnomei-seo:migrate-tobimori-v1' => self::definition(
                description: 'Migrate content and supported files from Tobimori SEO 1.1.2',
                adapterFactory: static fn(App $kirby): MigrationAdapter => new TobimoriV112MigrationAdapter(),
            ),
            'bnomei-seo:migrate-tobimori-v2' => self::definition(
                description: 'Migrate content and supported files from Tobimori SEO current main / v2',
                adapterFactory: static fn(App $kirby): MigrationAdapter => new TobimoriV2MigrationAdapter(),
            ),
            'bnomei-seo:migrate-fabianmeta' => self::definition(
                description: 'Migrate content and supported files from Fabian Michael Meta',
                adapterFactory: static fn(App $kirby): MigrationAdapter => new FabianMetaMigrationAdapter(),
            ),
        ];
    }

    /**
     * @param \Closure(App): MigrationAdapter $adapterFactory
     * @return array<string, mixed>
     */
    private static function definition(string $description, \Closure $adapterFactory): array
    {
        return [
            'description' => $description,
            'args' => [
                'apply' => [
                    'longPrefix' => 'apply',
                    'prefix' => 'a',
                    'description' => 'Persist the planned migration changes',
                    'noValue' => true,
                ],
            ],
            'command' => static function ($cli) use ($adapterFactory): MigrationReport {
                $kirby = App::instance();
                $adapter = $adapterFactory($kirby);
                $report = MigrationService::for($kirby)->run($adapter, self::applyFlag($cli));

                self::write($cli, $report->summaryLines());
                self::write($cli, $report->warnings, 'warning');
                self::write($cli, $report->followUps, 'line');

                return $report;
            },
        ];
    }

    private static function applyFlag(mixed $cli): bool
    {
        if (is_object($cli) === true && method_exists($cli, 'arg') === true) {
            $value = $cli->arg('apply');

            if (is_bool($value) === true) {
                return $value;
            }

            if (is_string($value) === true) {
                return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
            }
        }

        return false;
    }

    /**
     * @param string[] $lines
     */
    private static function write(mixed $cli, array $lines, string $method = 'line'): void
    {
        if ($lines === [] || is_object($cli) === false || method_exists($cli, $method) === false) {
            return;
        }

        foreach ($lines as $line) {
            $cli->{$method}($line);
        }
    }
}
