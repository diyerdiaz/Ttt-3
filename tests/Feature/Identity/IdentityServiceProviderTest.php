<?php

declare(strict_types=1);

use App\Providers\IdentityServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Password;

it('does not call the pwned passwords API outside production', function (): void {
    $passwordFails = static fn (string $password): bool => validator(['p' => $password], ['p' => Password::default()])->fails();
    Http::fake();

    expect($passwordFails('password1234'))->toBeFalse();

    Http::assertNothingSent();
});

it('rejects compromised passwords in production', function (): void {
    $passwordFails = static fn (string $password): bool => validator(['p' => $password], ['p' => Password::default()])->fails();
    $this->inEnvironment('production');
    (new IdentityServiceProvider($this->app))->boot();
    Http::fake(['api.pwnedpasswords.com/*' => Http::response(substr(strtoupper(sha1('password1234')), 5).':9999')]);

    expect($passwordFails('password1234'))->toBeTrue()
        ->and($passwordFails('a-very-unlikely-pass-9271'))->toBeFalse();
});
