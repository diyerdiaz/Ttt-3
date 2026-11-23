<?php

declare(strict_types=1);

use Tests\Support\Auth;

it('throttles login after five failed attempts per email and ip', function (): void {
    foreach (range(1, 5) as $_) {
        Auth::attemptLogin($this, 'ada@example.com')->assertSessionHasErrors('email');
    }

    Auth::attemptLogin($this, 'ada@example.com')->assertRedirect()->assertSessionHasErrors(['email' => 'Demasiados intentos. Inténtalo de nuevo más tarde.']);
});

it('counts the email without distinguishing case or spaces', function (): void {
    foreach (['ada@example.com', 'ADA@example.com', ' Ada@Example.com', 'ada@EXAMPLE.com', 'AdA@example.com'] as $email) {
        Auth::attemptLogin($this, $email);
    }

    Auth::attemptLogin($this, 'ada@example.com')->assertRedirect()->assertSessionHasErrors(['email' => 'Demasiados intentos. Inténtalo de nuevo más tarde.']);
});

it('does not throttle another email from the same ip nor the same email from another ip', function (): void {
    foreach (range(1, 5) as $_) {
        Auth::attemptLogin($this, 'ada@example.com');
    }

    Auth::attemptLogin($this, 'other@example.com')->assertSessionHasErrors('email');
    Auth::attemptLogin($this, 'ada@example.com', ip: '10.0.0.2')->assertSessionHasErrors('email');
});

it('throttles twenty attempts per minute per ip across emails', function (): void {
    foreach (range(1, 20) as $i) {
        Auth::attemptLogin($this, "user{$i}@example.com")->assertSessionHasErrors('email');
    }

    Auth::attemptLogin($this, 'user21@example.com')->assertRedirect()->assertSessionHasErrors(['email' => 'Demasiados intentos. Inténtalo de nuevo más tarde.']);
    Auth::attemptLogin($this, 'user21@example.com', ip: '10.0.0.9')->assertSessionHasErrors('email');
});

it('clears the email throttle after a successful login', function (): void {
    Auth::admin();

    foreach (range(1, 4) as $_) {
        Auth::attemptLogin($this, 'ada@example.com');
    }

    Auth::attemptLogin($this, 'ADA@example.com', Auth::STRONG_PASSWORD)->assertRedirect('/');
    $this->post('/logout');

    foreach (range(1, 5) as $_) {
        Auth::attemptLogin($this, 'ada@example.com')->assertSessionHasErrors('email');
    }
});

it('throttles fifty attempts per hour per account even across different ips', function (): void {
    foreach (range(1, 50) as $i) {
        Auth::attemptLogin($this, 'ada@example.com', ip: "10.0.1.{$i}")->assertSessionHasErrors(['email' => Auth::GENERIC_ERROR]);
    }

    Auth::attemptLogin($this, 'ADA@example.com', ip: '10.0.2.1')
        ->assertSessionHasErrors(['email' => 'Demasiados intentos. Inténtalo de nuevo más tarde.']);
    Auth::attemptLogin($this, 'other@example.com', ip: '10.0.2.1')->assertSessionHasErrors(['email' => Auth::GENERIC_ERROR]);
});

it('returns to the login form with the previous email when throttled', function (): void {
    foreach (range(1, 5) as $_) {
        Auth::attemptLogin($this, 'ada@example.com');
    }

    $this->from('/login')->post('/login', ['email' => 'ada@example.com', 'password' => 'x'])
        ->assertRedirect('/login')
        ->assertSessionHasInput('email', 'ada@example.com');
});
