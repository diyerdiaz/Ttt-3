<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Leads\Models\Lead;
use App\Http\Livewire\Activities\ActivityTimeline;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

final class TimelineComponent
{
    public static function open(Lead $lead): Testable
    {
        return Livewire::test(ActivityTimeline::class, ['leadId' => $lead->id]);
    }
}
