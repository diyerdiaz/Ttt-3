<?php

declare(strict_types=1);

use Tests\Support\Auth;

it('sends hardening headers on the login page', function (): void {
    $this->get('/login')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'")
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'same-origin');
});

it('sends hardening headers on protected pages and redirects', function (): void {
    $this->get('/')->assertRedirect('/login')->assertHeader('X-Frame-Options', 'DENY');

    $this->actingAs(Auth::admin())->get('/')
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'")
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'same-origin');
});

it('sends HSTS only in production over https', function (): void {
    $this->get('https://localhost/login')->assertHeaderMissing('Strict-Transport-Security');

    $this->app->detectEnvironment(static fn (): string => 'production');

    $this->get('http://localhost/login')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});
