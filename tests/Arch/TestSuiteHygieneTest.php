<?php

declare(strict_types=1);

it('declares no global functions or constants outside tests/Support and tests/Fixtures', function (): void {
    $root = dirname(__DIR__);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    $offenders = [];

    foreach ($files as $file) {
        $relative = substr((string) $file, strlen($root) + 1);

        if ($file->getExtension() !== 'php' || str_starts_with($relative, 'Support/') || str_starts_with($relative, 'Fixtures/')) {
            continue;
        }

        if (preg_match('/^(function\s+\w+|const\s+\w+)/m', (string) file_get_contents((string) $file), $match) === 1) {
            $offenders[] = $relative.': '.$match[1];
        }
    }

    expect($offenders)->toBe([]);
});
