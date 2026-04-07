<?php

declare(strict_types=1);

it('keeps the core layer free of kirby dependencies', function (): void {
    $violations = [];

    foreach (phpFilesIn(dirname(__DIR__, 2) . '/src/Core') as $file) {
        $content = file_get_contents($file->getPathname());

        if ($content === false) {
            $violations[] = $file->getPathname() . ': unreadable';
            continue;
        }

        if (str_contains($content, 'Kirby\\') || str_contains($content, 'Bnomei\\Seo\\Kirby\\')) {
            $violations[] = $file->getPathname();
        }
    }

    expect($violations)->toBeEmpty();
});

it('keeps plugin wiring thin by avoiding seo decisions in index registration', function (): void {
    $content = file_get_contents(dirname(__DIR__, 2) . '/index.php');

    expect($content)->not->toContain('new MetaResolver');
    expect($content)->not->toContain('new SitemapResolver');
    expect($content)->not->toContain('new RobotsResolver');
});

/**
 * @return iterable<\SplFileInfo>
 */
function phpFilesIn(string $directory): iterable
{
    if (is_dir($directory) === false) {
        return [];
    }

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($directory)
    );

    foreach ($iterator as $file) {
        if ($file instanceof \SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
            yield $file;
        }
    }
}
