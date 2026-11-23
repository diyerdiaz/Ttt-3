<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('redirects guests from protected routes to login', function (): void {
    $this->get('/')->assertRedirect('/login');
});

it('forbids authenticated non-admin users', function (): void {
    $this->actingAs(User::factory()->create())->get('/')->assertForbidden();
});

it('shows the dashboard placeholder to admins', function (): void {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/')
        ->assertOk()
        ->assertSee('Leads')
        ->assertSee('action="'.route('logout').'"', false)
        ->assertSee('name="_token"', false);
});

it('no longer ships the welcome view', function (): void {
    expect(view()->exists('welcome'))->toBeFalse();
});

it('grants the access-admin gate only to admins', function (): void {
    expect(Gate::forUser(User::factory()->admin()->make())->allows('access-admin'))->toBeTrue()
        ->and(Gate::forUser(User::factory()->make())->allows('access-admin'))->toBeFalse()
        ->and(Gate::allows('access-admin'))->toBeFalse();
});

it('registers the admin middleware alias', function (): void {
    expect(Route::getMiddleware())->toHaveKey('admin', EnsureUserIsAdmin::class);
});

it('lets an admin through the middleware and rejects guests and non-admins with 403', function (): void {
    $middleware = new EnsureUserIsAdmin;
    $next = static fn (): Response => response('ok');
    $request = Request::create('/');

    $request->setUserResolver(static fn (): User => User::factory()->admin()->make());
    expect($middleware->handle($request, $next)->getContent())->toBe('ok');

    $request->setUserResolver(static fn (): User => User::factory()->make());
    expect(fn () => $middleware->handle($request, $next))->toThrow(HttpException::class);

    $request->setUserResolver(static fn (): null => null);
    expect(fn () => $middleware->handle($request, $next))->toThrow(HttpException::class);
});

it('adds no queries beyond loading the user on an authenticated dashboard request', function (): void {
    $admin = User::factory()->admin()->create();

    DB::enableQueryLog();
    DB::flushQueryLog();
    $this->withSession([Auth::guard()->getName() => $admin->getKey()])->get('/')->assertOk();
    $queries = DB::getQueryLog();

    expect($queries)->toHaveCount(1)
        ->and($queries[0]['query'])->toContain('from "users"');
});

it('does not expose registration or password reset routes', function (): void {
    $uris = collect(Route::getRoutes()->getRoutes())->map(fn ($route): string => $route->uri());

    expect($uris->filter(fn (string $uri): bool => preg_match('/register|password|forgot|reset|verify/i', $uri) === 1))->toBeEmpty();
    $this->get('/register')->assertNotFound();
    $this->get('/forgot-password')->assertNotFound();
    $this->get('/password/reset')->assertNotFound();
});

it('protects every route except login, logout, health and livewire internals with auth and admin', function (): void {
    $public = ['login', 'logout', 'up'];

    $unprotected = collect(Route::getRoutes()->getRoutes())
        ->reject(fn ($route): bool => in_array($route->uri(), $public, true) || str_starts_with($route->uri(), 'livewire') || str_starts_with($route->uri(), 'storage/'))
        ->reject(fn ($route): bool => in_array('auth', $route->gatherMiddleware(), true) && in_array('admin', $route->gatherMiddleware(), true))
        ->map(fn ($route): string => $route->uri());

    expect($unprotected->all())->toBe([]);
});
