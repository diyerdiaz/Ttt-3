<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

final class LeadRows
{
    /**
     * Inserts a raw row, bypassing Eloquent so database constraints are exercised directly.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function insert(array $overrides = []): void
    {
        DB::table('leads')->insert(array_merge([
            'name' => 'Ada Lovelace',
            'status' => 'new',
            'position' => 0,
        ], $overrides));
    }

    /**
     * The UNIQUE (status, position) constraint is deferred; force the check now (inside a test transaction it never commits).
     */
    public static function assertPositionsAreUnique(): void
    {
        DB::statement('SET CONSTRAINTS leads_status_position_unique IMMEDIATE');
    }

    /**
     * Bulk-inserts $count leads (unique positions per status) with a fast raw insert, for volume tests.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function insertMany(int $count, array $overrides = [], int $firstPosition = 0): void
    {
        foreach (array_chunk(range(0, $count - 1), 1000) as $chunk) {
            DB::table('leads')->insert(array_map(
                static fn (int $i): array => array_merge([
                    'name' => 'Lead '.$i,
                    'email' => 'lead'.$i.'@example.test',
                    'status' => 'new',
                    'position' => $firstPosition + $i,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $overrides),
                $chunk,
            ));
        }
    }
}
