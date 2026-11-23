<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Identity\Actions\CreateAdminUser;
use App\Application\Identity\DTOs\NewUserData;
use App\Domain\Identity\Exceptions\AdminAlreadyExists;
use App\Domain\Shared\ValueObjects\EmailAddress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(CreateAdminUser $createAdmin): void
    {
        $email = config('crm.admin.email');
        $password = config('crm.admin.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            throw new RuntimeException('ADMIN_EMAIL y ADMIN_PASSWORD deben estar definidos en el .env.');
        }

        if (Validator::make(['password' => $password], ['password' => Password::default()])->fails()) {
            throw new RuntimeException('ADMIN_PASSWORD no cumple los requisitos de contraseña (mínimo 12 caracteres).');
        }

        try {
            $createAdmin(new NewUserData('Admin', EmailAddress::fromString($email), $password));
        } catch (InvalidArgumentException) {
            throw new RuntimeException('ADMIN_EMAIL no es un email válido.');
        } catch (AdminAlreadyExists) {
            // Idempotente: el administrador ya existe.
        }
    }
}
