<?php

declare(strict_types=1);

use Tests\Support\ExportProbe;
use Tests\Support\LeadRows;

// Measured: 3.2 MB for 5000 leads with lazyById; 20.7 MB (and 19.7 MB for the 500-vs-5000 comparison) with ->get().
it('exports 5000 leads without loading them all in memory', function (): void {
    LeadRows::insertMany(5000, ['notes' => str_repeat('n', 2000)]);

    expect(ExportProbe::memoryGrowthInBytes())->toBeLessThan(6 * 1024 * 1024);
});

it('uses the same memory for 500 and for 5000 leads', function (): void {
    LeadRows::insertMany(500, ['notes' => str_repeat('n', 2000)]);
    $small = ExportProbe::memoryGrowthInBytes();

    LeadRows::insertMany(4500, ['notes' => str_repeat('n', 2000)], firstPosition: 500);
    $large = ExportProbe::memoryGrowthInBytes();

    expect($large)->toBeLessThan($small * 1.5 + 1024 * 1024);
});
