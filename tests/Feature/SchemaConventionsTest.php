<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('uses timestamptz for every timestamp column in the public schema', function (): void {
    $offenders = collect(DB::select(
        "select table_name || '.' || column_name as name from information_schema.columns
         where table_schema = 'public' and data_type = 'timestamp without time zone'
         order by 1",
    ))->pluck('name');

    expect($offenders->all())->toBe([]);
});
