<?php

declare(strict_types=1);

namespace Tests\Support;

final class SecurityLog
{
    /**
     * @return list<string>
     */
    public static function lines(string $directory): array
    {
        $lines = [];

        foreach (glob($directory.'/*.log') ?: [] as $file) {
            $lines = [...$lines, ...file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []];
        }

        return $lines;
    }
}
