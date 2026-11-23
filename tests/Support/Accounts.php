<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Identity\Models\User;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Shared fixtures for tests that need accounts; reused by every feature branch.
 */
final class Accounts
{
    /** Satisfies Password::defaults() (min 12). The single strong password used across tests. */
    public const string STRONG_PASSWORD = 'correct-horse-battery';

    public const string WRONG_PASSWORD = 'wrong-password-123';

    public const string GENERIC_ERROR = 'Credenciales inválidas';

    public static function admin(string $email = 'ada@example.com'): User
    {
        return User::factory()->admin()->create(['email' => $email, 'password' => self::STRONG_PASSWORD]);
    }

    public static function user(string $email = 'user@example.com'): User
    {
        return User::factory()->create(['email' => $email, 'password' => self::STRONG_PASSWORD]);
    }

    /**
     * @return TestResponse<Response>
     */
    public static function attemptLogin(TestCase $test, string $email, string $password = self::WRONG_PASSWORD, string $ip = '10.0.0.1'): TestResponse
    {
        return $test->withServerVariables(['REMOTE_ADDR' => $ip])->post('/login', ['email' => $email, 'password' => $password]);
    }
}
