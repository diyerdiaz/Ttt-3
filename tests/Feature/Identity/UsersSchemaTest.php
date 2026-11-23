<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('defaults is_admin to false and does not allow null', function (): void {
    DB::table('users')->insert(['name' => 'Raw', 'email' => 'raw@example.com', 'password' => 'x']);

    expect(User::query()->where('email', 'raw@example.com')->firstOrFail()->isAdmin())->toBeFalse();

    DB::table('users')->where('email', 'raw@example.com')->update(['is_admin' => null]);
})->throws(QueryException::class);

it('enforces email uniqueness case-insensitively at the database level', function (): void {
    DB::table('users')->insert(['name' => 'Raw', 'email' => 'Dup@Example.com', 'password' => 'x']);
    DB::table('users')->insert(['name' => 'Raw', 'email' => 'dup@example.COM', 'password' => 'x']);
})->throws(UniqueConstraintViolationException::class);

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

function usersMigration(): Migration
{
    return require database_path('migrations/2026_11_22_100000_add_is_admin_and_case_insensitive_email_to_users_table.php');
}

it('aligns the users columns with the design', function (): void {
    $columns = collect(DB::select(
        "select column_name, data_type, character_maximum_length from information_schema.columns where table_name = 'users'",
    ))->keyBy('column_name');

    expect($columns['name']->character_maximum_length)->toBe(120)
        ->and($columns['email']->character_maximum_length)->toBe(254)
        ->and($columns['created_at']->data_type)->toBe('timestamp with time zone')
        ->and($columns['updated_at']->data_type)->toBe('timestamp with time zone')
        ->and($columns['email_verified_at']->data_type)->toBe('timestamp with time zone');
});

it('stops the migration with a clear error listing emails that differ only by case', function (): void {
    $migration = usersMigration();
    $migration->down();

    DB::table('users')->insert([
        ['name' => 'A', 'email' => 'Dup@Example.com', 'password' => 'x'],
        ['name' => 'B', 'email' => 'dup@example.com', 'password' => 'x'],
        ['name' => 'C', 'email' => 'unique@example.com', 'password' => 'x'],
    ]);

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, 'dup@example.com');
});

it('can be rolled back and applied again', function (): void {
    $migration = usersMigration();
    $migration->down();

    expect(Schema::hasColumn('users', 'is_admin'))->toBeFalse();

    $migration->up();

    expect(Schema::hasColumn('users', 'is_admin'))->toBeTrue();
});
