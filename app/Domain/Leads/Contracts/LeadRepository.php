<?php

declare(strict_types=1);

namespace App\Domain\Leads\Contracts;

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\ValueObjects\LeadData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface LeadRepository
{
    public function find(int $id): ?Lead;

    /**
     * @throws ModelNotFoundException<Lead>
     */
    public function findOrFail(int $id): Lead;

    public function create(LeadData $data, int $position): Lead;

    /**
     * @param  int|null  $position  new position, or null to keep the current one
     */
    public function update(Lead $lead, LeadData $data, ?int $position = null): Lead;

    public function nextPositionIn(LeadStatus $status): int;

    /**
     * Case-insensitive search by name, email or company; a blank term lists everything, newest first.
     *
     * @return LengthAwarePaginator<int, Lead>
     */
    public function search(?string $term, int $perPage): LengthAwarePaginator;
}
