<?php

declare(strict_types=1);

namespace App\Http\RateLimiting;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\RateLimiter;

final class LoginRateLimiter
{
    public const string NAME = 'login';

    public static function register(): void
    {
        ThrottleRequests::shouldHashKeys(false);

        RateLimiter::for(self::NAME, static fn (Request $request): array => [
            Limit::perMinute(5)->by(self::emailKey($request)),
            Limit::perMinute(20)->by((string) $request->ip()),
        ]);
    }

    public static function clear(Request $request): void
    {
        RateLimiter::clear(self::NAME.':'.self::emailKey($request));
    }

    private static function emailKey(Request $request): string
    {
        $email = $request->input('email');

        return hash('sha256', mb_strtolower(trim(is_string($email) ? $email : '')).'|'.$request->ip());
    }
}
