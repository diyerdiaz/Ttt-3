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
