<?php

declare(strict_types=1);

use Tests\Support\ConfigEnv;

it('sends the session cookie only over https in production by default', function (): void {
    expect(ConfigEnv::evaluate('session', ['APP_ENV' => 'production', 'SESSION_SECURE_COOKIE' => null])['secure'])->toBeTrue()
        ->and(ConfigEnv::evaluate('session', ['APP_ENV' => 'local', 'SESSION_SECURE_COOKIE' => null])['secure'])->toBeFalse()
        ->and(ConfigEnv::evaluate('session', ['APP_ENV' => 'production', 'SESSION_SECURE_COOKIE' => 'false'])['secure'])->toBeFalse();
});

it('keeps the session cookie http only and same-site lax', function (): void {
    expect(config('session.http_only'))->toBeTrue()
        ->and(config('session.same_site'))->toBe('lax');
});

it('uses a 500 ms timebox for authentication by default and allows overriding it', function (): void {
    expect(ConfigEnv::evaluate('auth', ['AUTH_TIMEBOX_DURATION' => null])['timebox_duration'])->toBe(500000)
        ->and(ConfigEnv::evaluate('auth', ['AUTH_TIMEBOX_DURATION' => '800000'])['timebox_duration'])->toBe(800000);
});

it('does not serve the local disk publicly', function (): void {
    expect(config('filesystems.disks.local.serve'))->toBeFalse();
});

it('documents the session and timebox variables in .env.example', function (): void {
    $example = file_get_contents(base_path('.env.example'));

    expect($example)->toContain('SESSION_SECURE_COOKIE=')->toContain('AUTH_TIMEBOX_DURATION=');
});

it('casts numeric env values from .env files to integers', function (): void {
    expect(ConfigEnv::evaluate('hashing', ['BCRYPT_LIMIT' => '72'])['bcrypt']['limit'])->toBe(72)
        ->and(ConfigEnv::evaluate('hashing', ['BCRYPT_ROUNDS' => '12'])['bcrypt']['rounds'])->toBe(12)
        ->and(ConfigEnv::evaluate('logging', ['SECURITY_LOG_DAYS' => '30'])['channels']['security']['days'])->toBe(30)
        ->and(ConfigEnv::evaluate('auth', ['AUTH_TIMEBOX_DURATION' => '250000'])['timebox_duration'])->toBe(250000);
});

it('falls back to the defaults when .env leaves a variable empty, as .env.example does', function (): void {
    expect(ConfigEnv::evaluate('hashing', ['BCRYPT_LIMIT' => ''])['bcrypt']['limit'])->toBe(72)
        ->and(ConfigEnv::evaluate('hashing', ['BCRYPT_ROUNDS' => ''])['bcrypt']['rounds'])->toBe(12)
        ->and(ConfigEnv::evaluate('logging', ['SECURITY_LOG_DAYS' => ''])['channels']['security']['days'])->toBe(90)
        ->and(ConfigEnv::evaluate('auth', ['AUTH_TIMEBOX_DURATION' => ''])['timebox_duration'])->toBe(500000)
        ->and(ConfigEnv::evaluate('session', ['APP_ENV' => 'production', 'SESSION_SECURE_COOKIE' => ''])['secure'])->toBeTrue();
});
