<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;

it('persists a user from the domain identity model factory', function (): void {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(User::class)
        ->and(User::query()->count())->toBe(1)
        ->and(config('auth.providers.users.model'))->toBe(User::class);
});
