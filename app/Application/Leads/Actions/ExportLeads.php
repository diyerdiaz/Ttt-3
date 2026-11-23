<?php

declare(strict_types=1);

namespace App\Application\Leads\Actions;

use App\Domain\Leads\Contracts\LeadExporter;
use App\Domain\Leads\Contracts\LeadExportSource;
use App\Domain\Leads\ValueObjects\LeadExportFilter;

final readonly class ExportLeads
{
    public function __construct(private LeadExportSource $source) {}

    /**
     * @param  resource  $out
     */
    public function __invoke(LeadExportFilter $filter, LeadExporter $exporter, $out): void
    {
        $exporter->write($this->source->stream($filter), $out);
    }
}
