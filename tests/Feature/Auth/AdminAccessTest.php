<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\Accounts;
use Tests\Support\RouteGuard;

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
    it('puts every route except the public allowlist behind auth and admin', function (): void {
        expect(RouteGuard::unprotected())->toBe([]);
    });

    it('does not let a route opt out of auth or admin', function (): void {
        expect(RouteGuard::optOuts())->toBe([]);
    });

    it('detects a route that is missing the admin middleware', function (): void {
        Route::middleware(['web', 'auth'])->get('/leaky', static fn (): string => 'x');

        expect(RouteGuard::unprotected())->toBe(['GET /leaky']);
    });

    it('detects a route that opts out of admin', function (): void {
        Route::middleware(['web', 'auth', 'admin'])->withoutMiddleware('admin')->get('/opt-out', static fn (): string => 'x');

        expect(RouteGuard::optOuts())->toBe(['/opt-out'])
            ->and(RouteGuard::unprotected())->toBe(['GET /opt-out']);
    });
});
