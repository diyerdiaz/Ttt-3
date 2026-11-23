<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

it('keeps the CSRF middleware in the web group used by login and logout', function (): void {
    $web = app('router')->getMiddlewareGroups()['web'];
    $csrfMiddleware = array_filter($web, static fn (string $m): bool => is_a($m, ValidateCsrfToken::class, true) || is_a($m, PreventRequestForgery::class, true));

    expect($csrfMiddleware)->not->toBeEmpty();

    foreach (['login', 'logout'] as $uri) {
        $route = collect(Route::getRoutes()->getRoutes())->first(fn ($r): bool => $r->uri() === $uri && in_array('POST', $r->methods(), true));
        expect($route?->gatherMiddleware())->toContain('web');
    }
});

it('rejects a login post without a CSRF token once the testing bypass is off', function (): void {
    $this->app->detectEnvironment(static fn (): string => 'local');

    $this->post('/login', ['email' => 'ada@example.com', 'password' => 'whatever-password'])->assertStatus(419);
});

it('accepts a login post carrying the session CSRF token', function (): void {
    $this->app->detectEnvironment(static fn (): string => 'local');

    $this->withSession(['_token' => 'known-token'])
        ->post('/login', ['_token' => 'known-token', 'email' => 'ada@example.com', 'password' => 'whatever-password'])
        ->assertSessionHasErrors('email');
});
