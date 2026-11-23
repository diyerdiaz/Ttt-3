<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\TestDatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    private const array FORCED_ENV = ['APP_ENV', 'APP_CONFIG_CACHE', 'DB_CONNECTION', 'DB_DATABASE', 'DB_URL'];

    // phpunit.xml fuerza estas variables en getenv()/$_ENV, pero Laravel lee antes $_SERVER,
    // que conserva el valor exportado en la shell. Se sincroniza para que el force sea efectivo.
    public function createApplication()
    {
        foreach (self::FORCED_ENV as $name) {
            $value = getenv($name);

            if ($value !== false) {
                $_SERVER[$name] = $value;
            }
        }

        return parent::createApplication();
    }

    /**
     * @return array<class-string, class-string>
     */
    protected function setUpTraits(): array
    {
        TestDatabaseGuard::assertSafe(
            (string) $this->app['env'],
            (string) $this->app['db']->connection()->getDatabaseName(),
        );

        return parent::setUpTraits();
    }
}
