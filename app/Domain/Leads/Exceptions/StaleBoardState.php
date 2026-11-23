<?php

declare(strict_types=1);

namespace App\Domain\Leads\Exceptions;

use App\Domain\Leads\Enums\LeadStatus;
use DomainException;

final class StaleBoardState extends DomainException
{
    public static function forColumn(LeadStatus $column): self
    {
        return new self("The {$column->label()} column changed since the board was loaded.");
    }
}
