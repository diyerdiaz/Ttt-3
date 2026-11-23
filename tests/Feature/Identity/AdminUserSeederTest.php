<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Tests\Support\Auth;

it('seeds the admin from the env credentials', function (): void {
    config(['crm.admin.email' => 'Boss@Example.com', 'crm.admin.password' => Auth::STRONG_PASSWORD]);

    $this->seed(AdminUserSeeder::class);

    $user = User::query()->where('email', 'boss@example.com')->firstOrFail();

    expect($user->isAdmin())->toBeTrue()
        ->and(Hash::check(Auth::STRONG_PASSWORD, $user->password))->toBeTrue();
});

it('fails instead of inventing credentials when the env vars are missing', function (?string $email, ?string $password): void {
    config(['crm.admin.email' => $email, 'crm.admin.password' => $password]);

    expect(fn () => $this->seed(AdminUserSeeder::class))->toThrow(RuntimeException::class, 'ADMIN_EMAIL');
    expect(User::query()->count())->toBe(0);
})->with([
    'no email' => [null, Auth::STRONG_PASSWORD],
    'no password' => ['boss@example.com', null],
    'empty values' => ['', ''],
]);

it('fails without creating any user when the env password is weak', function (): void {
    config(['crm.admin.email' => 'boss@example.com', 'crm.admin.password' => 'short']);

    expect(fn () => $this->seed(AdminUserSeeder::class))->toThrow(RuntimeException::class);
    expect(User::query()->count())->toBe(0);
});

it('is idempotent when the admin already exists', function (): void {
    config(['crm.admin.email' => 'boss@example.com', 'crm.admin.password' => Auth::STRONG_PASSWORD]);

    $this->seed(AdminUserSeeder::class);
    $this->seed(AdminUserSeeder::class);

    expect(User::query()->count())->toBe(1);
});

it('seeds only the admin from the database seeder', function (): void {
    config(['crm.admin.email' => 'boss@example.com', 'crm.admin.password' => Auth::STRONG_PASSWORD]);

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(1);
});

it('fails instead of silently skipping when the email belongs to a non-admin user', function (): void {
    User::factory()->create(['email' => 'boss@example.com']);
    config(['crm.admin.email' => 'boss@example.com', 'crm.admin.password' => Auth::STRONG_PASSWORD]);

    expect(fn () => $this->seed(AdminUserSeeder::class))->toThrow(RuntimeException::class, 'no es administrador');
    expect(User::query()->where('is_admin', true)->count())->toBe(0);
});

it('rejects an invalid ADMIN_EMAIL', function (): void {
    config(['crm.admin.email' => 'not-an-email', 'crm.admin.password' => Auth::STRONG_PASSWORD]);

    expect(fn () => $this->seed(AdminUserSeeder::class))->toThrow(RuntimeException::class, 'ADMIN_EMAIL');
});
