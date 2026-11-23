<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

final class ActivityRows
{
    /**
     * Inserts a raw row, bypassing Eloquent so database constraints are exercised directly.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function insert(int $leadId, array $overrides = []): void
    {
        DB::table('activities')->insert(array_merge([
            'lead_id' => $leadId,
            'description' => 'Called the lead',
            'occurred_at' => now(),
        ], $overrides));
    }
}
