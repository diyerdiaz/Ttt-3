<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Leads\Actions\ExportLeads;
use App\Domain\Leads\Contracts\LeadExporterFactory;
use App\Domain\Leads\Contracts\LeadExportSource;
use App\Domain\Leads\ValueObjects\LeadExportFilter;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Helpers for the export tests: HTTP download, source ids and memory measurement.
 */
final class ExportProbe
{
    /**
     * @param  array<string, mixed>  $query
     * @return TestResponse<Response>
     */
    public static function download(TestCase $test, array $query = []): TestResponse
    {
        return $test->get('/leads/export'.($query === [] ? '' : '?'.http_build_query($query)));
    }

    /**
     * @param  TestResponse<Response>  $response
     * @return list<string> names found in the data rows of a downloaded csv
     */
    public static function names(TestResponse $response): array
    {
        return array_column(array_slice(CsvDownload::rows($response->streamedContent()), 1), 1);
    }

    /**
     * @return list<int>
     */
    public static function ids(LeadExportFilter $filter): array
    {
        $ids = [];

        foreach (app(LeadExportSource::class)->stream($filter) as $lead) {
            $ids[] = $lead->id;
        }

        return $ids;
    }

    /**
     * Memory growth while the export runs, sampled with memory_get_usage() each time the source
     * hands over a lead (max sample minus the level before the export). It is deterministic:
     * unlike memory_get_peak_usage(), whose peak is only updated when the allocator takes a new
     * page (so it often reports no growth at all), it sees every lead and counts what is held at
     * that moment: a fully loaded collection would show up in full. The export goes to a real
     * file, not to memory, so only the pipeline is measured.
     */
    public static function memoryGrowthInBytes(): int
    {
        $samples = [];
        app()->instance(LeadExportSource::class, new SamplingLeadExportSource(app(LeadExportSource::class), $samples));
        $out = tmpfile();
        assert($out !== false);
        $action = app(ExportLeads::class);
        $exporter = app(LeadExporterFactory::class)->for('csv');

        gc_collect_cycles();
        $before = memory_get_usage();

        $action(new LeadExportFilter([]), $exporter, $out);

        fclose($out);

        return max($samples) - $before;
    }
}
