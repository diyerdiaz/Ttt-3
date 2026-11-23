<?php

declare(strict_types=1);

namespace App\Domain\Leads\ValueObjects;

use App\Domain\Leads\Enums\LeadStatus;

/**
 * Which leads to export. An empty list of statuses means every lead.
 */
final readonly class LeadExportFilter
{
    /**
     * @param  list<LeadStatus>  $statuses
     */
    public function __construct(public array $statuses = []) {}

    public function isUnfiltered(): bool
    {
        return $this->statuses === [];
    }
}
