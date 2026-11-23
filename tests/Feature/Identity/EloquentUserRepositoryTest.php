<?php

declare(strict_types=1);

use App\Domain\Identity\Contracts\UserRepository;
use App\Domain\Identity\Models\User;
use App\Domain\Shared\ValueObjects\EmailAddress;
use App\Infrastructure\Persistence\EloquentUserRepository;

it('binds the user repository contract to the Eloquent implementation', function (): void {
    expect(app(UserRepository::class))->toBeInstanceOf(EloquentUserRepository::class);
});

it('creates an admin user persisted with a normalized email', function (): void {
    $user = app(UserRepository::class)->createAdmin('Ada', EmailAddress::fromString('Ada@Example.com'), 'hash');

    expect($user->exists)->toBeTrue()
        ->and($user->isAdmin())->toBeTrue()
        ->and(User::query()->where('email', 'ada@example.com')->firstOrFail()->isAdmin())->toBeTrue();
});

it('finds a user by email without distinguishing case', function (): void {
    User::factory()->create(['email' => 'ada@example.com']);

    $repository = app(UserRepository::class);

    expect($repository->findByEmail(EmailAddress::fromString('ADA@example.com')))->toBeInstanceOf(User::class)
        ->and($repository->findByEmail(EmailAddress::fromString('nobody@example.com')))->toBeNull();
});
