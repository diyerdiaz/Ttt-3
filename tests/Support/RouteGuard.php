<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;

/**
 * Finds routes that are reachable without being an authenticated admin. Reusable by every
 * feature branch: add a route outside the allowlist without auth+admin and the suite fails.
 */
final class RouteGuard
{
    /** Routes reachable without being an admin, by route name. */
    public const array PUBLIC_ROUTES = ['login', 'login.store', 'logout'];

    /**
     * @return list<string> "METHOD uri" of every route lacking effective auth or admin middleware
     */
    public static function unprotected(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->reject(static fn (LaravelRoute $route): bool => in_array($route->getName(), self::PUBLIC_ROUTES, true) || self::isFrameworkRoute($route))
            ->filter(static function (LaravelRoute $route): bool {
                $effective = app('router')->gatherRouteMiddleware($route);

                return ! in_array(Authenticate::class, $effective, true)
                    || ! in_array(EnsureUserIsAdmin::class, $effective, true);
            })
            ->map(static fn (LaravelRoute $route): string => $route->methods()[0].' /'.ltrim($route->uri(), '/'))
            ->values()
            ->all();
    }

    /**
     * @return list<string> uris of routes that exclude auth or admin through withoutMiddleware()
     */
    public static function optOuts(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(static fn (LaravelRoute $route): bool => array_intersect($route->excludedMiddleware(), ['auth', 'admin', Authenticate::class, EnsureUserIsAdmin::class]) !== [])
            ->map(static fn (LaravelRoute $route): string => '/'.ltrim($route->uri(), '/'))
            ->values()
            ->all();
    }

    private static function isFrameworkRoute(LaravelRoute $route): bool
    {
        return $route->uri() === 'up' || str_starts_with($route->uri(), 'livewire-');
    }
}
