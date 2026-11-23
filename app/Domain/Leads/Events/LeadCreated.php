<?php

declare(strict_types=1);

namespace App\Domain\Leads\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class LeadCreated implements ShouldDispatchAfterCommit
{
    public function __construct(public int $leadId) {}
}
