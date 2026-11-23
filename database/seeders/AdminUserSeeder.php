<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Identity\Actions\CreateAdminUser;
use App\Application\Identity\DTOs\NewUserData;
use App\Domain\Identity\Contracts\UserRepository;
use App\Domain\Identity\Exceptions\EmailAlreadyTaken;
use App\Domain\Identity\Exceptions\WeakPassword;
use App\Domain\Shared\ValueObjects\EmailAddress;
use Illuminate\Database\Seeder;
use InvalidArgumentException;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(CreateAdminUser $createAdmin, UserRepository $users): void
    {
        $email = config('crm.admin.email');
        $password = config('crm.admin.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            throw new RuntimeException('ADMIN_EMAIL y ADMIN_PASSWORD deben estar definidos en el .env.');
        }

        try {
            $address = EmailAddress::fromString($email);
        } catch (InvalidArgumentException) {
            throw new RuntimeException('ADMIN_EMAIL no es un email válido.');
        }

        try {
            $createAdmin(new NewUserData(CreateAdminUser::DEFAULT_NAME, $address, $password));
        } catch (WeakPassword $exception) {
            throw new RuntimeException('ADMIN_PASSWORD: '.$exception->getMessage(), previous: $exception);
        } catch (EmailAlreadyTaken) {
            if ($users->findByEmail($address)?->isAdmin() !== true) {
                throw new RuntimeException("El usuario {$address->value()} existe pero no es administrador.");
            }
        }
    }
}
