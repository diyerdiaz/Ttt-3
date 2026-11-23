<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Tests\Support\AuditLog;
use Tests\Support\Auth;

beforeEach(function (): void {
    $this->channel = Mockery::spy(LoggerInterface::class);
    Log::shouldReceive('channel')->with('security')->andReturn($this->channel);
});

it('logs a failed login with a hashed email, ip and user agent but never the password', function (): void {
    $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.3', 'HTTP_USER_AGENT' => 'PestAgent'])
        ->post('/login', ['email' => ' Ada@Example.com ', 'password' => Auth::WRONG_PASSWORD]);

    $context = AuditLog::context($this->channel, 'warning', 'auth.login_failed');

    expect($context)->toMatchArray([
        'email_hash' => hash('sha256', 'ada@example.com'),
        'ip' => '10.1.2.3',
        'user_agent' => 'PestAgent',
    ])->and(json_encode($context))->not->toContain(Auth::WRONG_PASSWORD)->not->toContain('ada@example.com');
});

it('logs successful logins and logouts with the user id', function (): void {
    $admin = Auth::admin();

    $this->post('/login', ['email' => $admin->email, 'password' => Auth::STRONG_PASSWORD]);
    expect(AuditLog::context($this->channel, 'info', 'auth.login'))->toMatchArray(['user_id' => $admin->getKey()]);

    $this->post('/logout');
    expect(AuditLog::context($this->channel, 'info', 'auth.logout'))->toMatchArray(['user_id' => $admin->getKey()]);
});

it('logs throttled login attempts', function (): void {
    foreach (range(1, 6) as $_) {
        Auth::attemptLogin($this, 'ada@example.com');
    }

    expect(AuditLog::context($this->channel, 'warning', 'auth.login_throttled'))
        ->toMatchArray(['email_hash' => hash('sha256', 'ada@example.com'), 'ip' => '10.0.0.1']);
});

it('defines a dedicated security log channel', function (): void {
    expect(config('logging.channels.security.driver'))->toBe('single')
        ->and(config('logging.channels.security.path'))->toEndWith('logs/security.log');
});
