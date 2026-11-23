<?php

declare(strict_types=1);

namespace App\Domain\Leads\Contracts;

interface LeadBoardRepository
{
    /**
     * Serializes every write that touches status/position until the current transaction ends.
     */
    public function lockBoard(): void;
}
