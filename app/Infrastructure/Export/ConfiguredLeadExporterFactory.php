<?php

declare(strict_types=1);

namespace App\Infrastructure\Export;

use App\Domain\Leads\Contracts\LeadExporter;
use App\Domain\Leads\Contracts\LeadExporterFactory;
use App\Domain\Leads\Exceptions\UnsupportedExportFormat;

/**
 * Adding a format (xlsx, json) is a new LeadExporter plus one line here.
 */
final class ConfiguredLeadExporterFactory implements LeadExporterFactory
{
    public function for(string $format): LeadExporter
    {
        return match ($format) {
            'csv' => new CsvLeadExporter,
            default => throw UnsupportedExportFormat::for($format),
        };
    }
}
