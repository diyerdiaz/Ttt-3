<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Identity\Contracts\UserRepository;
use App\Domain\Identity\Exceptions\EmailAlreadyTaken;
use App\Domain\Identity\Models\User;
use App\Domain\Shared\ValueObjects\EmailAddress;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentUserRepository implements UserRepository
{
    public function findByEmail(EmailAddress $email): ?User
    {
        return User::query()->whereRaw('lower(email) = ?', [$email->value()])->first();
    }

    public function createAdmin(string $name, EmailAddress $email, string $passwordHash): User
    {
        $user = new User(['name' => $name, 'email' => $email->value(), 'password' => $passwordHash]);
        $user->is_admin = true;

        try {
            $user->save();
        } catch (UniqueConstraintViolationException) {
            throw EmailAlreadyTaken::withEmail($email);
        }

        return $user;
    }
}
