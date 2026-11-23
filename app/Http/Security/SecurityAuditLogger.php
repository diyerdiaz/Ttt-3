<?php

declare(strict_types=1);

namespace App\Http\Security;

use App\Domain\Shared\ValueObjects\EmailAddress;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Writes authentication events to the "security" channel. Emails are logged only as a
 * sha256 hash and passwords are never read.
 */
final readonly class SecurityAuditLogger
{
    public const string CHANNEL = 'security';

    public function __construct(private Request $request) {}

    public function failed(): void
    {
        Log::channel(self::CHANNEL)->warning('auth.login_failed', $this->requestEmailContext());
    }

    public function throttled(): void
    {
        Log::channel(self::CHANNEL)->warning('auth.login_throttled', $this->requestEmailContext());
    }

    public function loggedIn(Login $event): void
    {
        Log::channel(self::CHANNEL)->info('auth.login', $this->userContext($event->user->getAuthIdentifier()));
    }

    public function loggedOut(Logout $event): void
    {
        Log::channel(self::CHANNEL)->info('auth.logout', $this->userContext($event->user->getAuthIdentifier()));
    }

    /**
     * @return array<string, string|null>
     */
    private function requestEmailContext(): array
    {
        $email = $this->request->input('email');

        return ['email_hash' => hash('sha256', EmailAddress::normalize(is_string($email) ? $email : ''))] + $this->requestContext();
    }

    /**
     * @return array<string, mixed>
     */
    private function userContext(mixed $userId): array
    {
        return ['user_id' => $userId] + $this->requestContext();
    }

    /**
     * @return array<string, string|null>
     */
    private function requestContext(): array
    {
        return ['ip' => $this->request->ip(), 'user_agent' => $this->request->userAgent()];
    }
}
