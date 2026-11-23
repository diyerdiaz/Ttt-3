<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

function insertUser(string $email): bool
{
    return DB::table('users')->insert([
        'name' => 'Raw',
        'email' => $email,
        'password' => 'x',
    ]);
}

it('defaults is_admin to false and does not allow null', function (): void {
    insertUser('raw@example.com');

    expect(User::query()->where('email', 'raw@example.com')->firstOrFail()->isAdmin())->toBeFalse();

    DB::table('users')->where('email', 'raw@example.com')->update(['is_admin' => null]);
})->throws(QueryException::class);

it('enforces email uniqueness case-insensitively at the database level', function (): void {
    insertUser('Dup@Example.com');
    insertUser('dup@example.COM');
})->throws(UniqueConstraintViolationException::class);

it('uses a functional unique index on lower(email)', function (): void {
    $definitions = collect(DB::select("select indexdef from pg_indexes where tablename = 'users'"))
        ->pluck('indexdef')->implode(' ');

    expect($definitions)->toContain('UNIQUE INDEX')->toContain('lower((email)::text)');
});

it('lowercases and trims the email when saving a user', function (): void {
    $user = User::factory()->create(['email' => '  Mixed@Example.COM ']);

    expect($user->email)->toBe('mixed@example.com')
        ->and($user->fresh()?->email)->toBe('mixed@example.com');
});

it('exposes isAdmin through the model and the admin factory state', function (): void {
    expect(User::factory()->create()->isAdmin())->toBeFalse()
        ->and(User::factory()->admin()->create()->isAdmin())->toBeTrue();
});

it('does not allow mass assigning is_admin', function (): void {
    $user = new User(['name' => 'N', 'email' => 'n@example.com', 'password' => 'secret-password', 'is_admin' => true]);
})->throws(MassAssignmentException::class);
