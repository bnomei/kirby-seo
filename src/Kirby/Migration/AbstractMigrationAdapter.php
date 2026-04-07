<?php

declare(strict_types=1);

namespace Bnomei\Seo\Kirby\Migration;

use Bnomei\Seo\Kirby\SeoFields;
use Kirby\Cms\App;
use Kirby\Cms\ModelWithContent;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Stringable;
use Throwable;

abstract class AbstractMigrationAdapter implements MigrationAdapter
{
    public function planModel(Page|Site $model, string $languageCode): ?MigrationModelChange
    {
        $writes = [];
        $skipped = [];
        $warnings = $this->warningsFor($model, $languageCode);

        foreach (SeoFields::migratable() as $targetField) {
            $nativeValue = $this->nativeValue($model, $languageCode, $targetField);

            if ($nativeValue !== null) {
                $skipped[$targetField] = 'native-already-set';
                continue;
            }

            $value = $this->explicitValue($model, $languageCode, $targetField);
            $reason = 'explicit-source';

            if ($value === null) {
                $value = $this->resolvedValue($model, $languageCode, $targetField);
                $reason = 'resolved-fallback';
            }

            if ($value === null) {
                $skipped[$targetField] = 'source-empty';
                continue;
            }

            $writes[$targetField] = $value;
            $skipped[$targetField] = $reason;
        }

        if ($writes === [] && $warnings === []) {
            return null;
        }

        return MigrationModelChange::fromModel($model, $languageCode, $writes, $skipped, $warnings);
    }

    public function planFileChanges(App $kirby): array
    {
        $changes = [];

        foreach ($this->scanFiles($kirby->root('blueprints'), ['php', 'yml', 'yaml']) as $path) {
            $change = $this->rewriteBlueprintFile($path);

            if ($change !== null) {
                $changes[] = $change;
            }
        }

        foreach ([$kirby->root('site') . '/templates', $kirby->root('site') . '/snippets'] as $root) {
            foreach ($this->scanFiles($root, ['php']) as $path) {
                $change = $this->rewritePhpFile($path);

                if ($change !== null) {
                    $changes[] = $change;
                }
            }
        }

        return $changes;
    }

    public function manualFollowUps(App $kirby): array
    {
        return $this->configuredOptionWarnings($kirby);
    }

    abstract protected function explicitValue(Page|Site $model, string $languageCode, string $targetField): ?string;

    protected function resolvedValue(Page|Site $model, string $languageCode, string $targetField): ?string
    {
        return null;
    }

    /**
     * @return string[]
     */
    protected function warningsFor(Page|Site $model, string $languageCode): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    protected function blueprintReplacements(): array
    {
        return [];
    }

    /**
     * @return array<int, array{pattern: string, replace: string, label: string}>
     */
    protected function phpReplacements(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    protected function optionWarnings(): array
    {
        return [];
    }

    protected function nativeValue(ModelWithContent $model, string $languageCode, string $field): ?string
    {
        return $this->contentValue($model, $languageCode, $field);
    }

    protected function contentValue(ModelWithContent $model, string $languageCode, string $field): ?string
    {
        foreach ([$model->content($languageCode)->get($field), $model->content()->get($field)] as $fieldObject) {
            $value = $fieldObject->value();

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @param string[] $fields
     */
    protected function firstContentValue(ModelWithContent $model, string $languageCode, array $fields): ?string
    {
        foreach ($fields as $field) {
            $value = $this->contentValue($model, $languageCode, $field);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    protected function normalizedIndexValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtolower(trim($value));

        if ($value === '') {
            return null;
        }

        if (str_contains($value, 'noindex')) {
            return '0';
        }

        if (str_contains($value, 'index')) {
            return '1';
        }

        return match ($value) {
            '1', 'true', 'yes' => '1',
            '0', 'false', 'no' => '0',
            default => null,
        };
    }

    /**
     * @param string[] $fields
     */
    protected function nonEmptyLegacyFields(Page|Site $model, string $languageCode, array $fields): array
    {
        $warnings = [];

        foreach ($fields as $field) {
            $value = $this->contentValue($model, $languageCode, $field);

            if ($value === null) {
                continue;
            }

            $warnings[] =
                $this->warningPrefix($model, $languageCode)
                . 'unsupported field `'
                . $field
                . '` requires manual follow-up.';
        }

        return $warnings;
    }

    protected function warningPrefix(Page|Site $model, string $languageCode): string
    {
        $modelLabel = $model instanceof Site ? 'site' : 'page `' . $model->id() . '`';

        return $this->label() . ' ' . $modelLabel . ' [' . $languageCode . ']: ';
    }

    /**
     * @return string[]
     */
    protected function configuredOptionWarnings(App $kirby): array
    {
        $sentinel = new \stdClass();
        $warnings = [];

        foreach ($this->optionWarnings() as $optionKey => $message) {
            if ($kirby->option($optionKey, $sentinel) !== $sentinel) {
                $warnings[] = $message;
            }
        }

        return $warnings;
    }

    protected function metadataLookup(mixed $source, array $candidates): ?string
    {
        $queue = [$source];
        $seenObjects = [];

        while ($queue !== []) {
            $current = array_shift($queue);

            if (is_array($current)) {
                foreach ($candidates as $candidate) {
                    if (array_key_exists($candidate, $current) === false) {
                        continue;
                    }

                    $normalized = $this->normalizeScalar($current[$candidate]);

                    if ($normalized !== null) {
                        return $normalized;
                    }
                }

                foreach ($current as $value) {
                    if (is_array($value) || is_object($value)) {
                        $queue[] = $value;
                    }
                }

                continue;
            }

            if (is_object($current) === false) {
                continue;
            }

            $objectId = spl_object_id($current);

            if (isset($seenObjects[$objectId]) === true) {
                continue;
            }

            $seenObjects[$objectId] = true;

            foreach ($candidates as $candidate) {
                try {
                    if (property_exists($current, $candidate) === true) {
                        $normalized = $this->normalizeScalar($current->{$candidate});

                        if ($normalized !== null) {
                            return $normalized;
                        }
                    }
                } catch (Throwable) {
                }

                if (method_exists($current, $candidate) === false) {
                    continue;
                }

                try {
                    $normalized = $this->normalizeScalar($current->{$candidate}());

                    if ($normalized !== null) {
                        return $normalized;
                    }
                } catch (Throwable) {
                }
            }

            foreach (['snippetData', 'toArray'] as $method) {
                if (method_exists($current, $method) === false) {
                    continue;
                }

                try {
                    $nested = $current->{$method}();

                    if (is_array($nested) === true) {
                        $queue[] = $nested;
                    }
                } catch (Throwable) {
                }
            }
        }

        return null;
    }

    protected function normalizeScalar(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value) === true) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value) === true) {
            $value = trim($value);

            return $value !== '' ? $value : null;
        }

        if ($value instanceof Stringable) {
            $value = trim((string) $value);

            return $value !== '' ? $value : null;
        }

        if (is_array($value) === true) {
            $parts = [];

            foreach ($value as $nested) {
                $normalized = $this->normalizeScalar($nested);

                if ($normalized !== null) {
                    $parts[] = $normalized;
                }
            }

            return $parts !== [] ? implode(',', $parts) : null;
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function scanFiles(string $root, array $extensions): array
    {
        if (is_dir($root) === false) {
            return [];
        }

        $paths = [];

        foreach (Dir::index($root, true) as $relativePath) {
            $path = $root . '/' . $relativePath;

            if (is_file($path) === false) {
                continue;
            }

            if (in_array(pathinfo($path, PATHINFO_EXTENSION), $extensions, true) === true) {
                $paths[] = $path;
            }
        }

        sort($paths);

        return $paths;
    }

    private function rewriteBlueprintFile(string $path): ?MigrationFileChange
    {
        if ($this->blueprintReplacements() === []) {
            return null;
        }

        $content = F::read($path);
        $updated = $content;
        $rewrites = [];

        foreach ($this->blueprintReplacements() as $from => $to) {
            if (str_contains($updated, $from) === false) {
                continue;
            }

            $updated = str_replace($from, $to, $updated);
            $rewrites[] = $from . ' -> ' . $to;
        }

        if ($updated === $content) {
            return null;
        }

        return new MigrationFileChange(path: $path, kind: 'blueprint', rewrites: $rewrites, updatedContent: $updated);
    }

    private function rewritePhpFile(string $path): ?MigrationFileChange
    {
        if ($this->phpReplacements() === []) {
            return null;
        }

        $content = F::read($path);
        $updated = $content;
        $rewrites = [];

        foreach ($this->phpReplacements() as $rule) {
            $next = preg_replace($rule['pattern'], $rule['replace'], $updated);

            if ($next === null || $next === $updated) {
                continue;
            }

            $updated = $next;
            $rewrites[] = $rule['label'];
        }

        if ($updated === $content) {
            return null;
        }

        return new MigrationFileChange(path: $path, kind: 'php', rewrites: $rewrites, updatedContent: $updated);
    }
}
