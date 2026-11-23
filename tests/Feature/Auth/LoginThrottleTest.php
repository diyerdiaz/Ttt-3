<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;

function attemptLogin(string $email, string $password = 'wrong-password-123', string $ip = '10.0.0.1')
{
    return test()->withServerVariables(['REMOTE_ADDR' => $ip])->post('/login', ['email' => $email, 'password' => $password]);
}

it('throttles login after five failed attempts per email and ip', function (): void {
    foreach (range(1, 5) as $_) {
        attemptLogin('ada@example.com')->assertSessionHasErrors('email');
    }

    attemptLogin('ada@example.com')->assertTooManyRequests();
});

it('counts the email without distinguishing case or spaces', function (): void {
    foreach (['ada@example.com', 'ADA@example.com', ' Ada@Example.com', 'ada@EXAMPLE.com', 'AdA@example.com'] as $email) {
        attemptLogin($email);
    }

    attemptLogin('ada@example.com')->assertTooManyRequests();
});

it('does not throttle another email from the same ip nor the same email from another ip', function (): void {
    foreach (range(1, 5) as $_) {
        attemptLogin('ada@example.com');
    }

    attemptLogin('other@example.com')->assertSessionHasErrors('email');
    attemptLogin('ada@example.com', ip: '10.0.0.2')->assertSessionHasErrors('email');
});

it('throttles twenty attempts per minute per ip across emails', function (): void {
    foreach (range(1, 20) as $i) {
        attemptLogin("user{$i}@example.com")->assertSessionHasErrors('email');
    }

    attemptLogin('user21@example.com')->assertTooManyRequests();
    attemptLogin('user21@example.com', ip: '10.0.0.9')->assertSessionHasErrors('email');
});

it('clears the email throttle after a successful login', function (): void {
    User::factory()->admin()->create(['email' => 'ada@example.com', 'password' => 'correct-horse-battery']);

    foreach (range(1, 4) as $_) {
        attemptLogin('ada@example.com');
    }

    attemptLogin('ADA@example.com', 'correct-horse-battery')->assertRedirect('/');
    test()->post('/logout');

    foreach (range(1, 5) as $_) {
        attemptLogin('ada@example.com')->assertSessionHasErrors('email');
    }
});
