<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

const STRONG_PASSWORD = 'correct-horse-battery';

it('applies a minimum length of 12 to the default password rule', function (): void {
    $rule = Password::default();

    expect(validator(['p' => 'short-pass1'], ['p' => $rule])->fails())->toBeTrue()
        ->and(validator(['p' => 'twelve-chars'], ['p' => $rule])->fails())->toBeFalse();
});

it('creates an admin via the crm:create-admin command', function (): void {
    $this->artisan('crm:create-admin', ['email' => 'Ada@Example.com', '--name' => 'Ada'])
        ->expectsQuestion('Contraseña', STRONG_PASSWORD)
        ->expectsQuestion('Confirma la contraseña', STRONG_PASSWORD)
        ->expectsOutputToContain('ada@example.com')
        ->assertSuccessful();

    $user = User::query()->where('email', 'ada@example.com')->firstOrFail();

    expect($user->isAdmin())->toBeTrue()
        ->and($user->name)->toBe('Ada')
        ->and(Hash::check(STRONG_PASSWORD, $user->password))->toBeTrue();
});

it('uses a default name when none is given', function (): void {
    $this->artisan('crm:create-admin', ['email' => 'ada@example.com'])
        ->expectsQuestion('Contraseña', STRONG_PASSWORD)
        ->expectsQuestion('Confirma la contraseña', STRONG_PASSWORD)
        ->assertSuccessful();

    expect(User::query()->firstOrFail()->name)->toBe('Admin');
});

it('fails the command with a weak password', function (): void {
    $this->artisan('crm:create-admin', ['email' => 'ada@example.com'])
        ->expectsQuestion('Contraseña', 'short')
        ->expectsQuestion('Confirma la contraseña', 'short')
        ->expectsOutputToContain('at least 12')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('fails the command when the confirmation does not match', function (): void {
    $this->artisan('crm:create-admin', ['email' => 'ada@example.com'])
        ->expectsQuestion('Contraseña', STRONG_PASSWORD)
        ->expectsQuestion('Confirma la contraseña', 'something-else-entirely')
        ->expectsOutputToContain('no coinciden')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('fails the command with an invalid email without asking for a password', function (): void {
    $this->artisan('crm:create-admin', ['email' => 'not-an-email'])
        ->expectsOutputToContain('email')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('rejects a duplicate email regardless of case with a clear error', function (): void {
    User::factory()->create(['email' => 'ada@example.com']);

    $this->artisan('crm:create-admin', ['email' => 'ADA@example.com'])
        ->expectsQuestion('Contraseña', STRONG_PASSWORD)
        ->expectsQuestion('Confirma la contraseña', STRONG_PASSWORD)
        ->expectsOutputToContain('already exists')
        ->assertFailed();

    expect(User::query()->count())->toBe(1);
});
