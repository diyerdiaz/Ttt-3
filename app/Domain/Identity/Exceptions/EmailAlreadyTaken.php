<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\ValueObjects\EmailAddress;
use RuntimeException;

final class EmailAlreadyTaken extends RuntimeException
{
    public static function withEmail(EmailAddress $email): self
    {
        return new self(sprintf('A user with email %s already exists.', $email->value()));
    }
}
