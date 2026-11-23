<?php

declare(strict_types=1);

namespace App\Domain\Identity\Contracts;

use App\Domain\Identity\Models\User;
use App\Domain\Shared\ValueObjects\EmailAddress;

interface UserRepository
{
    public function findByEmail(EmailAddress $email): ?User;

    public function createAdmin(string $name, EmailAddress $email, string $passwordHash): User;
}
