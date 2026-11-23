<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use App\Infrastructure\Export\CsvLeadExporter;

/**
 * Builds in-memory leads and renders them through the real CSV exporter.
 */
final class LeadCsv
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function lead(array $attributes = []): Lead
    {
        return (new Lead)->forceFill(array_merge([
            'id' => 1,
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
            'phone' => '+57 300 111 2222',
            'company' => 'Analytical',
            'notes' => 'Primera nota',
            'status' => LeadStatus::Won,
            'created_at' => '2026-11-20 15:30:00',
        ], $attributes));
    }

    /**
     * @param  iterable<Lead>  $leads
     */
    public static function render(iterable $leads): string
    {
        $out = fopen('php://memory', 'w+b');
        assert($out !== false);
        (new CsvLeadExporter)->write($leads, $out);
        rewind($out);

        return (string) stream_get_contents($out);
    }
}
