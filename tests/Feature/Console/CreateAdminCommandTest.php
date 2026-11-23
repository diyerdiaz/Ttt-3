<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Support\Accounts;

it('creates an admin via the crm:create-admin command', function (): void {
    $this->artisan('crm:create-admin', ['email' => 'Ada@Example.com', '--name' => 'Ada'])
        ->expectsQuestion('Contraseña', Accounts::STRONG_PASSWORD)
        ->expectsQuestion('Confirma la contraseña', Accounts::STRONG_PASSWORD)
        ->expectsOutputToContain('ada@example.com')
        ->assertSuccessful();

    $user = User::query()->where('email', 'ada@example.com')->firstOrFail();

    expect($user->isAdmin())->toBeTrue()
        ->and($user->name)->toBe('Ada')
        ->and(Hash::check(Accounts::STRONG_PASSWORD, $user->password))->toBeTrue();
});

it('uses a default name when none is given', function (): void {
    $this->artisan('crm:create-admin', ['email' => 'ada@example.com'])
        ->expectsQuestion('Contraseña', Accounts::STRONG_PASSWORD)
        ->expectsQuestion('Confirma la contraseña', Accounts::STRONG_PASSWORD)
        ->assertSuccessful();

    expect(User::query()->firstOrFail()->name)->toBe('Admin');
});

it('fails the command with a weak password', function (): void {
    $this->artisan('crm:create-admin', ['email' => 'ada@example.com'])
        ->expectsQuestion('Contraseña', 'short')
        ->expectsQuestion('Confirma la contraseña', 'short')
        ->expectsOutputToContain('entre 12 y 72 caracteres')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('fails the command when the confirmation does not match', function (): void {
    $this->artisan('crm:create-admin', ['email' => 'ada@example.com'])
        ->expectsQuestion('Contraseña', Accounts::STRONG_PASSWORD)
        ->expectsQuestion('Confirma la contraseña', 'something-else-entirely')
        ->expectsOutputToContain('no coinciden')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('fails the command with an invalid email without asking for a password', function (): void {
    $this->artisan('crm:create-admin', ['email' => 'not-an-email'])
        ->expectsOutputToContain('no es válido')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('rejects a duplicate email regardless of case with a clear error', function (): void {
    User::factory()->create(['email' => 'ada@example.com']);

    $this->artisan('crm:create-admin', ['email' => 'ADA@example.com'])
        ->expectsQuestion('Contraseña', Accounts::STRONG_PASSWORD)
        ->expectsQuestion('Confirma la contraseña', Accounts::STRONG_PASSWORD)
        ->expectsOutputToContain('Ya existe un usuario')
        ->assertFailed();

    expect(User::query()->count())->toBe(1);
});

it('rejects a name longer than 120 characters before asking for a password', function (): void {
    $this->artisan('crm:create-admin', ['email' => 'ada@example.com', '--name' => str_repeat('n', 121)])
        ->expectsOutputToContain('120')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('fails without asking for a password when there is no interactive terminal', function (): void {
    $this->artisan('crm:create-admin', ['email' => 'ada@example.com', '--no-interaction' => true])
        ->expectsOutputToContain('terminal interactiva')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('fails without creating anything when the password answer is empty', function (?string $answer): void {
    $this->artisan('crm:create-admin', ['email' => 'ada@example.com'])
        ->expectsQuestion('Contraseña', $answer)
        ->expectsOutputToContain('no puede estar vacía')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
})->with([
    'no answer' => [null],
    'empty' => [''],
    'blank' => ['   '],
]);
