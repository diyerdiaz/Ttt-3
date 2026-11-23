<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('runs the test suite against the pgsql crm_test database', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql')
        ->and(DB::connection()->getDatabaseName())->toBe('crm_test');
});

it('talks to PostgreSQL in UTC, so naive timestamps written by Laravel keep their instant', function (): void {
    expect(DB::selectOne('show timezone')->TimeZone)->toBe('UTC');
});
