<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Shared\ValueObjects\EmailAddress;
use App\Http\RateLimiting\LoginRateLimiter;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class LoginRequest extends FormRequest
{
    public const string INVALID_CREDENTIALS = 'Credenciales inválidas';

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:254'],
            'password' => ['required', 'string', 'max:1024'],
        ];
    }

    public function authenticate(): void
    {
        $email = EmailAddress::normalize($this->string('email')->toString());

        $authenticated = Auth::attempt([
            static fn (Builder $query): Builder => $query->whereRaw('lower(email) = ?', [$email]),
            'password' => $this->string('password')->toString(),
            'is_admin' => true,
        ]);

        if (! $authenticated) {
            throw ValidationException::withMessages(['email' => self::INVALID_CREDENTIALS]);
        }

        LoginRateLimiter::clear($this);
    }
}
