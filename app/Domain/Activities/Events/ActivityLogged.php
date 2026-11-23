<?php

declare(strict_types=1);

namespace App\Domain\Activities\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class ActivityLogged implements ShouldDispatchAfterCommit
{
    public function __construct(public int $activityId, public int $leadId) {}
}
