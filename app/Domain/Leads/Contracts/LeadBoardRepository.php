<?php

declare(strict_types=1);

namespace App\Domain\Leads\Contracts;

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;

interface LeadBoardRepository
{
    /**
     * Serializes every write that touches status/position until the current transaction ends.
     */
    public function lockBoard(): void;

    /**
     * Every status in board order, each with its leads ordered by position (cards only carry the columns they display).
     *
     * @return array<string, list<Lead>>
     */
    public function columns(): array;

    public function findForUpdate(int $id): Lead;

    /**
     * @return list<int> ids of the column in position order
     */
    public function idsInColumn(LeadStatus $status): array;

    /**
     * Puts the leads in the column in the given order, moving them into it if they come from another one.
     *
     * @param  list<int>  $orderedIds
     */
    public function placeColumn(LeadStatus $status, array $orderedIds): void;
}
