<?php

declare(strict_types=1);

use App\Application\Identity\Actions\CreateAdminUser;
use App\Application\Identity\DTOs\NewUserData;
use App\Domain\Identity\Exceptions\WeakPassword;
use App\Domain\Identity\Models\User;
use App\Domain\Shared\ValueObjects\EmailAddress;

it('limits bcrypt input to 72 bytes', function (): void {
    expect(config('hashing.bcrypt.limit'))->toBe(72);
});

it('rejects a 72-character multibyte password that exceeds 72 bytes as a weak password', function (): void {
    $password = str_repeat('ñ', 72);

    expect(mb_strlen($password))->toBe(72)->and(strlen($password))->toBe(144);

    expect(fn () => app(CreateAdminUser::class)(new NewUserData('Ada', EmailAddress::fromString('ada@example.com'), $password)))
        ->toThrow(WeakPassword::class);
    expect(User::query()->count())->toBe(0);
});

it('explains every requirement in the weak password message', function (): void {
    expect(WeakPassword::create()->getMessage())->toContain('12')->toContain('72')->toContain('filtraciones');
});
