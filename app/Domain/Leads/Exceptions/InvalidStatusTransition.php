<?php

declare(strict_types=1);

namespace App\Domain\Leads\Exceptions;

use App\Domain\Leads\Enums\LeadStatus;
use DomainException;

final class InvalidStatusTransition extends DomainException
{
    private function __construct(public readonly LeadStatus $from, public readonly LeadStatus $to)
    {
        parent::__construct("A lead cannot move from {$from->label()} to {$to->label()}.");
    }

    public static function between(LeadStatus $from, LeadStatus $to): self
    {
        return new self($from, $to);
    }
}
