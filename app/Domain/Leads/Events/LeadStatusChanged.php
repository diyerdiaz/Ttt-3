<?php

declare(strict_types=1);

namespace App\Domain\Leads\Events;

use App\Domain\Leads\Enums\LeadStatus;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class LeadStatusChanged implements ShouldDispatchAfterCommit
{
    public function __construct(
        public int $leadId,
        public LeadStatus $from,
        public LeadStatus $to,
    ) {}
}
