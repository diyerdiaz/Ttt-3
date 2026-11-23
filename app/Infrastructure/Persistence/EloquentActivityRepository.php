<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Activities\Contracts\ActivityRepository;
use App\Domain\Activities\Models\Activity;
use App\Domain\Activities\ValueObjects\ActivityData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentActivityRepository implements ActivityRepository
{
    private const array TIMELINE_COLUMNS = ['id', 'type', 'description', 'occurred_at'];

    public function create(int $leadId, ActivityData $data): Activity
    {
        $activity = new Activity([
            'type' => $data->type,
            'description' => $data->description,
            'occurred_at' => $data->occurredAt,
        ]);
        $activity->lead_id = $leadId;
        $activity->save();

        return $activity;
    }

    public function forLead(int $leadId, int $perPage): LengthAwarePaginator
    {
        return Activity::query()
            ->select(self::TIMELINE_COLUMNS)
            ->where('lead_id', $leadId)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
