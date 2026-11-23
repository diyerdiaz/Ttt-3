<?php

declare(strict_types=1);

use App\Application\Leads\Actions\ExportLeads;
use App\Domain\Leads\Contracts\LeadExporterFactory;
use App\Domain\Leads\ValueObjects\LeadExportFilter;
use Tests\Support\LeadRows;

/**
 * Peak memory above the level held before the export, reset with memory_reset_peak_usage()
 * (PHP 8.2+), so earlier allocations in the process do not leak into the measure. The export
 * goes to a real file, not to memory, so only the pipeline itself is measured.
 */
function exportPeakGrowthInBytes(): int
{
    $out = tmpfile();
    assert($out !== false);
    $action = app(ExportLeads::class);
    $exporter = app(LeadExporterFactory::class)->for('csv');

    gc_collect_cycles();
    memory_reset_peak_usage();
    $before = memory_get_usage();

    $action(new LeadExportFilter([]), $exporter, $out);

    $growth = memory_get_peak_usage() - $before;
    fclose($out);

    return $growth;
}

it('exports 5000 leads without loading them all in memory', function (): void {
    LeadRows::insertMany(5000, ['notes' => str_repeat('n', 2000)]);

    $growth = exportPeakGrowthInBytes();

    // 5000 hydrated leads with 2 KB notes need well over 25 MB; one chunk of 500 needs a few MB.
    expect($growth)->toBeLessThan(12 * 1024 * 1024);
});

it('uses the same peak memory for 500 and for 5000 leads', function (): void {
    LeadRows::insertMany(500, ['notes' => str_repeat('n', 2000)]);
    $small = exportPeakGrowthInBytes();

    LeadRows::insertMany(4500, ['notes' => str_repeat('n', 2000)], firstPosition: 500);
    $large = exportPeakGrowthInBytes();

    expect($large)->toBeLessThan($small * 1.5 + 1024 * 1024);
});
