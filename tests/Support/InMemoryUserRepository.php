<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Identity\Contracts\UserRepository;
use App\Domain\Identity\Models\User;
use App\Domain\Shared\ValueObjects\EmailAddress;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    public array $users = [];

    public function findByEmail(EmailAddress $email): ?User
    {
        return $this->users[$email->value()] ?? null;
    }

    public function createAdmin(string $name, EmailAddress $email, string $passwordHash): User
    {
        $user = new User(['name' => $name, 'email' => $email->value(), 'password' => $passwordHash]);
        $user->is_admin = true;

        return $this->users[$email->value()] = $user;
    }
}
