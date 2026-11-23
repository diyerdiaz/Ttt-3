<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Database\Factories\UserFactory;

it('declares the domain user as the factory model', function (): void {
    expect(UserFactory::new()->modelName())->toBe(User::class);
});
