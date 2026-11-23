<?php

declare(strict_types=1);

namespace App\Application\Identity\Actions;

use App\Application\Identity\DTOs\NewUserData;
use App\Domain\Identity\Contracts\UserRepository;
use App\Domain\Identity\Exceptions\EmailAlreadyTaken;
use App\Domain\Identity\Exceptions\WeakPassword;
use App\Domain\Identity\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

final readonly class CreateAdminUser
{
    public const string DEFAULT_NAME = 'Admin';

    public function __construct(
        private UserRepository $users,
        private Hasher $hasher,
    ) {}

    private function hash(string $plainPassword): string
    {
        try {
            return $this->hasher->make($plainPassword);
        } catch (InvalidArgumentException) {
            // bcrypt only reads 72 bytes; a multibyte password can pass the character limit and still exceed it.
            throw WeakPassword::create();
        }
    }

    /**
     * @throws WeakPassword
     * @throws EmailAlreadyTaken
     */
    public function __invoke(NewUserData $data): User
    {
        if (Validator::make(['password' => $data->plainPassword], ['password' => Password::default()])->fails()) {
            throw WeakPassword::create();
        }

        if ($this->users->findByEmail($data->email) instanceof User) {
            throw EmailAlreadyTaken::withEmail($data->email);
        }

        return $this->users->createAdmin($data->name, $data->email, $this->hash($data->plainPassword));
    }
}
