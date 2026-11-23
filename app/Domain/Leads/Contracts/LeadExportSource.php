<?php

declare(strict_types=1);

namespace App\Domain\Leads\Contracts;

use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\ValueObjects\LeadExportFilter;

interface LeadExportSource
{
    /**
     * Leads in ascending id order, read lazily in chunks so memory stays constant.
     *
     * @return iterable<Lead>
     */
    public function stream(LeadExportFilter $filter): iterable;
}
