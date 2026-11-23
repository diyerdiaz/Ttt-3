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
            Limit::perMinute(5)->by(self::emailIpKey($request))->response(self::throttledResponse(...)),
            Limit::perMinute(20)->by((string) $request->ip())->response(self::throttledResponse(...)),
            Limit::perHour(50)->by(self::accountKey($request))->response(self::throttledResponse(...)),
        ]);
    }

    /**
     * Resets the email+ip and account counters after a successful login (the per-IP one stays).
     */
    public static function clear(Request $request): void
    {
        foreach ([self::emailIpKey($request), self::accountKey($request)] as $limitKey) {
            // md5 only reproduces the key ThrottleRequests stores named limiters under; it is not a security primitive.
            RateLimiter::clear(hash('md5', self::NAME.$limitKey));
        }
    }

    private static function throttledResponse(Request $request): Response
    {
        resolve(SecurityAuditLogger::class)->throttled();

        return redirect()->route('login')
            ->withInput($request->only('email'))
            ->withErrors(['email' => self::THROTTLED_MESSAGE]);
    }

    private static function emailIpKey(Request $request): string
    {
        return self::hash(self::normalizedEmail($request).'|'.$request->ip());
    }

    private static function accountKey(Request $request): string
    {
        return 'account:'.self::hash(self::normalizedEmail($request));
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
