<?php

declare(strict_types=1);

namespace App\Domain\Leads\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Won = 'won';
    case Lost = 'lost';

    /**
     * @return list<self>
     */
    public static function boardOrder(): array
    {
        return [self::New, self::Contacted, self::Qualified, self::Proposal, self::Won, self::Lost];
    }

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nuevo',
            self::Contacted => 'Contactado',
            self::Qualified => 'Calificado',
            self::Proposal => 'Propuesta',
            self::Won => 'Ganado',
            self::Lost => 'Perdido',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Won || $this === self::Lost;
    }

    /**
     * Single extension point for transition rules; the MVP allows any move, including a same-status reorder.
     */
    public function canTransitionTo(self $to): bool
    {
        return true;
    }
}
