<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exceptions;

use RuntimeException;

final class WeakPassword extends RuntimeException
{
    public static function create(): self
    {
        return new self('La contraseña no es válida: debe tener entre 12 y 72 caracteres y no figurar en filtraciones conocidas.');
    }
}
