<?php

declare(strict_types=1);

use Tests\Support\TestDatabaseGuard;
use Tests\TestCase;

it('accepts the testing environment with the crm_test database', function (): void {
    expect(fn () => TestDatabaseGuard::assertSafe('testing', 'crm_test'))
        ->not->toThrow(RuntimeException::class);
});

it('aborts when the database is not crm_test', function (string $database): void {
    TestDatabaseGuard::assertSafe('testing', $database);
})->with(['crm', 'crm_prod', '', ':memory:'])->throws(RuntimeException::class);

it('aborts when the environment is not testing', function (string $env): void {
    TestDatabaseGuard::assertSafe($env, 'crm_test');
})->with(['local', 'production', ''])->throws(RuntimeException::class);

describe('TestCase wiring', function (): void {
    uses(TestCase::class);

    it('aborts before any trait (migrations) runs when the app points to another database', function (): void {
        config()->set('database.connections.pgsql.database', 'crm');
        config()->set('database.default', 'pgsql');
        $this->app['db']->purge();

        $setUpTraits = new ReflectionMethod($this, 'setUpTraits');
        $setUpTraits->invoke($this);
    })->throws(RuntimeException::class);
});
