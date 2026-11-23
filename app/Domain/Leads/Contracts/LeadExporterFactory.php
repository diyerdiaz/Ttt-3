<?php

declare(strict_types=1);

namespace App\Domain\Leads\Contracts;

use App\Domain\Leads\Exceptions\UnsupportedExportFormat;

interface LeadExporterFactory
{
    /**
     * @throws UnsupportedExportFormat
     */
    public function for(string $format): LeadExporter;
}
