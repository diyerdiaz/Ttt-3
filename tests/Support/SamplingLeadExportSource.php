<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Leads\Contracts\LeadExportSource;
use App\Domain\Leads\ValueObjects\LeadExportFilter;

/**
 * Decorator that records memory_get_usage() every time a lead is handed over.
 */
final class SamplingLeadExportSource implements LeadExportSource
{
    /**
     * @param  list<int>  $samples  filled by reference
     */
    public function __construct(private LeadExportSource $inner, private array &$samples) {}

    public function stream(LeadExportFilter $filter): iterable
    {
        foreach ($this->inner->stream($filter) as $lead) {
            $this->samples[] = memory_get_usage();

            yield $lead;
        }
    }
}
