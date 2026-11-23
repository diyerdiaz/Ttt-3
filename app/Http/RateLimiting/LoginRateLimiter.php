<?php

declare(strict_types=1);

namespace App\Http\RateLimiting;

use App\Domain\Shared\ValueObjects\EmailAddress;
use App\Http\Security\SecurityAuditLogger;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

final class LoginRateLimiter
{
    public const string NAME = 'login';

    public const string THROTTLED_MESSAGE = 'Demasiados intentos. Inténtalo de nuevo más tarde.';

    public static function register(): void
    {
        RateLimiter::for(self::NAME, static fn (Request $request): array => [
            Limit::perMinute(5)->by(self::hash(self::normalizedEmail($request).'|'.$request->ip()))->response(self::throttledResponse(...)),
            Limit::perMinute(20)->by((string) $request->ip())->response(self::throttledResponse(...)),
            Limit::perHour(50)->by('account:'.self::hash(self::normalizedEmail($request)))->response(self::throttledResponse(...)),
        ]);
    }

    /**
     * Resets the email+ip counter. The key mirrors how ThrottleRequests stores named limiters.
     */
    public static function clear(Request $request): void
    {
        $limiterKey = self::hash(self::normalizedEmail($request).'|'.$request->ip());

        // md5 here only reproduces the framework's storage key; it is not a security primitive.
        RateLimiter::clear(hash('md5', self::NAME.$limiterKey));
    }

    private static function throttledResponse(Request $request): Response
    {
        resolve(SecurityAuditLogger::class)->throttled();

        return redirect()->route('login')
            ->withInput($request->only('email'))
            ->withErrors(['email' => self::THROTTLED_MESSAGE]);
    }

    private static function normalizedEmail(Request $request): string
    {
        $email = $request->input('email');

        return EmailAddress::normalize(is_string($email) ? $email : '');
    }

    private static function hash(string $value): string
    {
        return hash('sha256', $value);
    }
}
