<?php

declare(strict_types=1);

namespace App\Domain\Activities\Enums;

enum ActivityType: string
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Llamada',
            self::Email => 'Correo',
            self::Meeting => 'Reunión',
            self::Note => 'Nota',
        };
    }
}
