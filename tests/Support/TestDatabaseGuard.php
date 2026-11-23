<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;

final class TestDatabaseGuard
{
    public const string DATABASE = 'crm_test';

    public static function assertSafe(string $environment, string $database): void
    {
        if ($environment !== 'testing' || $database !== self::DATABASE) {
            throw new RuntimeException(sprintf(
                'Tests abortados: se esperaba APP_ENV=testing y BD "%s", pero se obtuvo APP_ENV="%s" y BD "%s".',
                self::DATABASE,
                $environment,
                $database,
            ));
        }
    }
}
