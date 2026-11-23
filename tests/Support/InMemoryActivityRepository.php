<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Activities\Contracts\ActivityRepository;
use App\Domain\Activities\Models\Activity;
use App\Domain\Activities\ValueObjects\ActivityData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

/**
 * Fake of the activity contract for unit tests of Actions.
 */
final class InMemoryActivityRepository implements ActivityRepository
{
    /** @var list<Activity> */
    public array $activities = [];

    private int $lastId = 0;

    public function create(int $leadId, ActivityData $data): Activity
    {
        $activity = new Activity([
            'type' => $data->type,
            'description' => $data->description,
            'occurred_at' => $data->occurredAt,
        ]);
        $activity->setAttribute('lead_id', $leadId);
        $activity->setAttribute('id', ++$this->lastId);

        return $this->activities[] = $activity;
    }

    public function forLead(int $leadId, int $perPage): LengthAwarePaginator
    {
        $items = array_values(array_filter($this->activities, static fn (Activity $a): bool => $a->lead_id === $leadId));

        return new Paginator(array_slice($items, 0, $perPage), count($items), $perPage);
    }
}
