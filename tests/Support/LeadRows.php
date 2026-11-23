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
}
