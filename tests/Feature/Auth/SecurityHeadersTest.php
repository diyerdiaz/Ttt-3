<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tests\Support\Accounts;

it('sends hardening headers on the login page', function (): void {
    $this->get('/login')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'")
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'same-origin');
});

it('sends hardening headers on protected pages and redirects', function (): void {
    $this->get('/')->assertRedirect('/login')->assertHeader('X-Frame-Options', 'DENY');

    $this->actingAs(Accounts::admin())->get('/')
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'")
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'same-origin');
});

it('sends HSTS only in production over https', function (): void {
    $this->get('https://localhost/login')->assertHeaderMissing('Strict-Transport-Security');

    $this->inEnvironment('production');

    $this->get('http://localhost/login')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('does not override headers the application already set', function (): void {
    Route::middleware('web')->get('/__fixtures/own-headers', static fn () => response('x', 200, [
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'no-referrer',
    ]));

    $this->get('/__fixtures/own-headers')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('adds frame-ancestors to an existing content security policy without replacing it', function (): void {
    Route::middleware('web')->get('/__fixtures/own-csp', static fn () => response('x', 200, ['Content-Security-Policy' => "default-src 'self'"]));
    Route::middleware('web')->get('/__fixtures/own-csp-ancestors', static fn () => response('x', 200, ['Content-Security-Policy' => "frame-ancestors 'self'"]));

    $this->get('/__fixtures/own-csp')->assertHeader('Content-Security-Policy', "default-src 'self'; frame-ancestors 'none'");
    $this->get('/__fixtures/own-csp-ancestors')->assertHeader('Content-Security-Policy', "frame-ancestors 'self'");
});
