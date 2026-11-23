<?php

declare(strict_types=1);

namespace App\Application\Identity\Actions;

use App\Application\Identity\DTOs\NewUserData;
use App\Domain\Identity\Contracts\UserRepository;
use App\Domain\Identity\Exceptions\AdminAlreadyExists;
use App\Domain\Identity\Models\User;
use Illuminate\Contracts\Hashing\Hasher;

final readonly class CreateAdminUser
{
    public function __construct(
        private UserRepository $users,
        private Hasher $hasher,
    ) {}

    public function __invoke(NewUserData $data): User
    {
        if ($this->users->findByEmail($data->email) instanceof User) {
            throw AdminAlreadyExists::withEmail($data->email);
        }

        return $this->users->createAdmin(
            $data->name,
            $data->email,
            $this->hasher->make($data->plainPassword),
        );
    }
}
