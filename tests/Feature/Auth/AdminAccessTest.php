<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\Accounts;

it('redirects guests from protected routes to login', function (): void {
    $this->get('/')->assertRedirect('/login');
});

it('forbids authenticated non-admin users', function (): void {
    $this->actingAs(Accounts::user())->get('/')->assertForbidden();
});

it('shows the dashboard placeholder to admins', function (): void {
    $this->actingAs(Accounts::admin())
        ->get('/')
        ->assertOk()
        ->assertSee('Leads')
        ->assertSee('action="'.route('logout').'"', false)
        ->assertSee('name="_token"', false);
});

it('grants the access-admin gate only to admins', function (): void {
    expect(Gate::forUser(User::factory()->admin()->make())->allows('access-admin'))->toBeTrue()
        ->and(Gate::forUser(User::factory()->make())->allows('access-admin'))->toBeFalse()
        ->and(Gate::allows('access-admin'))->toBeFalse();
});

it('lets an admin through the middleware and rejects guests and non-admins with 403', function (): void {
    $middleware = new EnsureUserIsAdmin;
    $next = static fn (): Response => response('ok');
    $forbidden = static fn () => $middleware->handle(Request::create('/'), $next);

    $this->actingAs(User::factory()->admin()->make());
    expect($middleware->handle(Request::create('/'), $next)->getContent())->toBe('ok');

    $this->actingAs(User::factory()->make());
    expect($forbidden)->toThrow(HttpException::class);

    AuthFacade::logout();
    expect($forbidden)->toThrow(HttpException::class);
});

it('adds no queries beyond loading the user on an authenticated dashboard request', function (): void {
    $admin = Accounts::admin();

    // The test session driver is "array", so the only query is the user lookup; with the
    // database driver in production the session read/write add their own, independent of the admin check.
    DB::enableQueryLog();
    DB::flushQueryLog();
    $this->withSession([AuthFacade::guard()->getName() => $admin->getKey()])->get('/')->assertOk();
    $queries = DB::getQueryLog();

    expect($queries)->toHaveCount(1)
        ->and($queries[0]['query'])->toContain('from "users"');
});

it('does not expose registration or password reset routes', function (): void {
    $this->get('/register')->assertNotFound();
    $this->get('/forgot-password')->assertNotFound();
    $this->get('/password/reset')->assertNotFound();
});

describe('route protection', function (): void {
    /** Routes reachable without being an admin, by route name. */
    $public = ['login', 'login.store', 'logout'];

    $isFrameworkRoute = static fn (LaravelRoute $route): bool => $route->uri() === 'up'
        || str_starts_with($route->uri(), 'livewire-');

    it('puts every route except the public allowlist behind auth and admin', function () use ($public, $isFrameworkRoute): void {
        $unprotected = collect(Route::getRoutes()->getRoutes())
            ->reject(fn (LaravelRoute $route): bool => in_array($route->getName(), $public, true) || $isFrameworkRoute($route))
            ->filter(function (LaravelRoute $route): bool {
                $effective = app('router')->gatherRouteMiddleware($route);

                return ! in_array(Authenticate::class, $effective, true)
                    || ! in_array(EnsureUserIsAdmin::class, $effective, true);
            })
            ->map(fn (LaravelRoute $route): string => $route->methods()[0].' '.$route->uri());

        expect($unprotected->all())->toBe([]);
    });

    it('does not let a route opt out of auth or admin', function (): void {
        $optOuts = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $route): bool => array_intersect($route->excludedMiddleware(), ['auth', 'admin', Authenticate::class, EnsureUserIsAdmin::class]) !== [])
            ->map(fn (LaravelRoute $route): string => $route->uri());

        expect($optOuts->all())->toBe([]);
    });

    it('detects a route that is missing the admin middleware', function (): void {
        $route = Route::middleware(['web', 'auth'])->get('/leaky', static fn (): string => 'x');

        expect(app('router')->gatherRouteMiddleware($route))->not->toContain(EnsureUserIsAdmin::class);
    });
});
