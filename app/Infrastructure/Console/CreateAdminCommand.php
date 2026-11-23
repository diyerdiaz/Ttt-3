<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\Identity\Actions\CreateAdminUser;
use App\Application\Identity\DTOs\NewUserData;
use App\Domain\Identity\Exceptions\EmailAlreadyTaken;
use App\Domain\Identity\Exceptions\WeakPassword;
use App\Domain\Shared\ValueObjects\EmailAddress;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('crm:create-admin {email : Email del administrador} {--name= : Nombre del administrador}')]
#[Description('Crea el usuario administrador del CRM')]
final class CreateAdminCommand extends Command
{
    private const int MAX_NAME_LENGTH = 120;

    public function handle(CreateAdminUser $createAdmin): int
    {
        try {
            $email = EmailAddress::fromString((string) $this->argument('email'));
        } catch (InvalidArgumentException) {
            $this->components->error('El email no es válido.');

            return self::FAILURE;
        }

        $name = $this->option('name');
        $name = is_string($name) && $name !== '' ? $name : CreateAdminUser::DEFAULT_NAME;

        if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            $this->components->error('El nombre no puede superar los '.self::MAX_NAME_LENGTH.' caracteres.');

            return self::FAILURE;
        }

        $password = $this->secretAnswer('Contraseña');

        if ($password !== $this->secretAnswer('Confirma la contraseña')) {
            $this->components->error('Las contraseñas no coinciden.');

            return self::FAILURE;
        }

        try {
            $createAdmin(new NewUserData($name, $email, $password));
        } catch (WeakPassword $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        } catch (EmailAlreadyTaken) {
            $this->components->error("Ya existe un usuario con el email {$email->value()}.");

            return self::FAILURE;
        }

        $this->components->info("Administrador {$email->value()} creado.");

        return self::SUCCESS;
    }

    private function secretAnswer(string $question): string
    {
        $answer = $this->secret($question);

        return is_string($answer) ? $answer : '';
    }
}
