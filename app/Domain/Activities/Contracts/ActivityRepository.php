<?php

declare(strict_types=1);

namespace App\Domain\Activities\Contracts;

use App\Domain\Activities\Models\Activity;
use App\Domain\Activities\ValueObjects\ActivityData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ActivityRepository
{
    public function create(int $leadId, ActivityData $data): Activity;

    /**
     * Newest first (`occurred_at DESC, id DESC`), carrying only the columns the timeline paints.
     *
     * @return LengthAwarePaginator<int, Activity>
     */
    public function forLead(int $leadId, int $perPage): LengthAwarePaginator;
}
