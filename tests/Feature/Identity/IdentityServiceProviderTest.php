<?php

declare(strict_types=1);

use App\Providers\IdentityServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;

function passwordFails(string $password): bool
{
    return validator(['p' => $password], ['p' => Password::default()])->fails();
}

function pwnedResponseFor(string $password): string
{
    $sha1 = strtoupper(sha1($password));

    return substr($sha1, 5).':9999';
}

it('does not call the pwned passwords API outside production', function (): void {
    Http::fake();

    expect(passwordFails('password1234'))->toBeFalse();

    Http::assertNothingSent();
});

it('rejects compromised passwords in production', function (): void {
    $this->app->detectEnvironment(static fn (): string => 'production');
    (new IdentityServiceProvider($this->app))->boot();
    Http::fake(['api.pwnedpasswords.com/*' => Http::response(pwnedResponseFor('password1234'))]);

    expect(passwordFails('password1234'))->toBeTrue()
        ->and(passwordFails('a-very-unlikely-pass-9271'))->toBeFalse();
});
