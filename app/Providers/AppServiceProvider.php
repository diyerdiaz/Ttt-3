<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Identity\Models\User;
use App\Http\RateLimiting\LoginRateLimiter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $isProduction = $this->app->isProduction();

        Model::shouldBeStrict(! $isProduction);
        DB::prohibitDestructiveCommands($isProduction);
        Date::use(CarbonImmutable::class);
        Gate::define('access-admin', static fn (User $user): bool => $user->isAdmin());
        LoginRateLimiter::register();
        Password::defaults(static fn (): Password => Password::min(12));
    }
}
