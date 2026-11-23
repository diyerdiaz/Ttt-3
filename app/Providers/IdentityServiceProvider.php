<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Identity\Abilities;
use App\Domain\Identity\Models\User;
use App\Http\RateLimiting\LoginRateLimiter;
use App\Http\Security\SecurityAuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Features\SupportFileUploads\FilePreviewController;
use Livewire\Livewire;

final class IdentityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define(Abilities::ACCESS_ADMIN, static fn (User $user): bool => $user->isAdmin());

        Password::defaults(fn (): Password => $this->app->isProduction()
            ? Password::min(12)->max(72)->uncompromised()
            : Password::min(12)->max(72));

        LoginRateLimiter::register();

        Livewire::setUpdateRoute(static fn (array $handle, string $path) => Route::post($path, $handle)
            ->middleware(['web', 'auth', 'admin']));

        // The preview route only checks a signed URL; it still must not be reachable without an admin session.
        FilePreviewController::$middleware = ['web', 'auth', 'admin'];

        Event::listen(Failed::class, [SecurityAuditLogger::class, 'failed']);
        Event::listen(Login::class, [SecurityAuditLogger::class, 'loggedIn']);
        Event::listen(Logout::class, [SecurityAuditLogger::class, 'loggedOut']);
    }
}
