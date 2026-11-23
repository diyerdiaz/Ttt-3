<?php

declare(strict_types=1);

namespace App\Application\Activities\Actions;

use App\Domain\Activities\Contracts\ActivityRepository;
use App\Domain\Activities\Events\ActivityLogged;
use App\Domain\Activities\Models\Activity;
use App\Domain\Activities\ValueObjects\ActivityData;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class LogActivity
{
    public function __construct(private ActivityRepository $activities, private Dispatcher $events) {}

    public function __invoke(int $leadId, ActivityData $data): Activity
    {
        $activity = $this->activities->create($leadId, $data);

        $this->events->dispatch(new ActivityLogged($activity->id, $leadId));

        return $activity;
    }
}
