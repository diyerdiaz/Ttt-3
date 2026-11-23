<?php

declare(strict_types=1);

use App\Application\Identity\Actions\CreateAdminUser;
use App\Application\Identity\DTOs\NewUserData;
use App\Domain\Identity\Exceptions\AdminAlreadyExists;
use App\Domain\Shared\ValueObjects\EmailAddress;
use Illuminate\Hashing\BcryptHasher;
use Tests\Support\InMemoryUserRepository;
use Tests\TestCase;

// El cast `hashed` del modelo usa la facade Hash, que necesita el contenedor de la aplicación.
uses(TestCase::class);

it('creates an admin user with a hashed password', function (): void {
    $hasher = new BcryptHasher(['rounds' => 4]);
    $repository = new InMemoryUserRepository;

    $user = (new CreateAdminUser($repository, $hasher))(
        new NewUserData('Ada', EmailAddress::fromString('ADA@Example.com'), 'plain-password-123'),
    );

    expect($user->isAdmin())->toBeTrue()
        ->and($user->name)->toBe('Ada')
        ->and($user->email)->toBe('ada@example.com')
        ->and($user->password)->not->toBe('plain-password-123')
        ->and($hasher->check('plain-password-123', $user->password))->toBeTrue();
});

it('refuses to create an admin when the email already exists', function (): void {
    $action = new CreateAdminUser(new InMemoryUserRepository, new BcryptHasher(['rounds' => 4]));
    $action(new NewUserData('Ada', EmailAddress::fromString('ada@example.com'), 'plain-password-123'));

    $action(new NewUserData('Other', EmailAddress::fromString('ADA@example.com'), 'another-password-1'));
})->throws(AdminAlreadyExists::class, 'ada@example.com');
