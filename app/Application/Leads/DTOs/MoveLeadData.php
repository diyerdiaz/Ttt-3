<?php

declare(strict_types=1);

namespace App\Application\Leads\DTOs;

use App\Domain\Leads\Enums\LeadStatus;

final readonly class MoveLeadData
{
    /**
     * @param  list<int>  $orderedIds  the destination column as the user sees it after the drop, including the moved lead
     */
    public function __construct(
        public int $leadId,
        public LeadStatus $to,
        public array $orderedIds,
    ) {}
}
