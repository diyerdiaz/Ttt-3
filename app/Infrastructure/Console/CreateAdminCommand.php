<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\Identity\Actions\CreateAdminUser;
use App\Application\Identity\DTOs\NewUserData;
use App\Domain\Identity\Exceptions\AdminAlreadyExists;
use App\Domain\Shared\ValueObjects\EmailAddress;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

#[Signature('crm:create-admin {email : Email del administrador} {--name= : Nombre del administrador}')]
#[Description('Crea el usuario administrador del CRM')]
final class CreateAdminCommand extends Command
{
    private const string DEFAULT_NAME = 'Admin';

    public function handle(CreateAdminUser $createAdmin): int
    {
        try {
            $email = EmailAddress::fromString((string) $this->argument('email'));
        } catch (InvalidArgumentException) {
            $this->components->error('El email no es válido.');

            return self::FAILURE;
        }

        $password = $this->askPassword();

        if ($password === null) {
            return self::FAILURE;
        }

        $name = $this->option('name');

        try {
            $createAdmin(new NewUserData(is_string($name) && $name !== '' ? $name : self::DEFAULT_NAME, $email, $password));
        } catch (AdminAlreadyExists) {
            $this->components->error("Ya existe un usuario con el email {$email->value()}.");

            return self::FAILURE;
        }

        $this->components->info("Administrador {$email->value()} creado.");

        return self::SUCCESS;
    }

    private function askPassword(): ?string
    {
        $password = (string) $this->secret('Contraseña');

        if ($password !== (string) $this->secret('Confirma la contraseña')) {
            $this->components->error('Las contraseñas no coinciden.');

            return null;
        }

        $validator = Validator::make(['password' => $password], ['password' => Password::default()]);

        if ($validator->fails()) {
            $this->components->error('La contraseña no cumple los requisitos (mínimo 12 caracteres).');

            return null;
        }

        return $password;
    }
}
