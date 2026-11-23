<?php

declare(strict_types=1);

use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\Support\Accounts;

it('shows the login form to guests', function (): void {
    $this->get('/login')
        ->assertOk()
        ->assertSee('name="_token"', false)
        ->assertSee('action="'.route('login').'"', false)
        ->assertSee('type="password"', false);
});

it('redirects authenticated users away from the login form', function (): void {
    $this->actingAs(Accounts::admin())->get('/login')->assertRedirect('/');
});

it('logs in an admin with valid credentials and regenerates the session', function (): void {
    Accounts::admin();

    $this->startSession();
    $before = session()->getId();
    $token = csrf_token();

    $this->post('/login', ['_token' => $token, 'email' => 'ada@example.com', 'password' => Accounts::STRONG_PASSWORD])
        ->assertRedirect('/');

    $this->assertAuthenticated();
    expect(session()->getId())->not->toBe($before);
});

it('redirects to the intended url after login', function (): void {
    Route::middleware(['web', 'auth', 'admin'])->get('/x', static fn (): string => 'protected');
    Accounts::admin();

    $this->get('/x')->assertRedirect('/login');
    $this->post('/login', ['email' => 'ada@example.com', 'password' => Accounts::STRONG_PASSWORD])->assertRedirect('/x');
});

it('treats the email case-insensitively when authenticating', function (): void {
    Accounts::admin('ada@example.com');

    $this->post('/login', ['email' => '  ADA@Example.COM ', 'password' => Accounts::STRONG_PASSWORD])
        ->assertRedirect('/');

    $this->assertAuthenticated();
});

it('rejects invalid credentials with a generic error', function (): void {
    Accounts::admin();

    $this->from('/login')
        ->post('/login', ['email' => 'ada@example.com', 'password' => Accounts::WRONG_PASSWORD])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['email' => Accounts::GENERIC_ERROR])
        ->assertSessionHasInput('email', 'ada@example.com')
        ->assertSessionMissing('_old_input.password');

    $this->assertGuest();
});

it('does not reveal whether a user exists', function (): void {
    Accounts::admin();

    $wrongPassword = $this->post('/login', ['email' => 'ada@example.com', 'password' => Accounts::WRONG_PASSWORD])
        ->assertSessionHasErrors('email');
    $messageForKnown = session('errors')->first('email');

    $this->post('/login', ['email' => 'nobody@example.com', 'password' => Accounts::WRONG_PASSWORD])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toBe($messageForKnown)->toBe(Accounts::GENERIC_ERROR);
    $wrongPassword->assertRedirect();
});

it('rejects a non-admin user with valid credentials without creating a session', function (): void {
    Accounts::user('user@example.com');

    $this->post('/login', ['email' => 'user@example.com', 'password' => Accounts::STRONG_PASSWORD])
        ->assertSessionHasErrors(['email' => Accounts::GENERIC_ERROR]);

    $this->assertGuest();
});

it('validates that email and password are present', function (array $payload, string $field): void {
    $this->post('/login', $payload)->assertSessionHasErrors($field);
    $this->assertGuest();
})->with([
    'missing email' => [['password' => 'x'], 'email'],
    'missing password' => [['email' => 'ada@example.com'], 'password'],
    'overlong password' => [['email' => 'ada@example.com', 'password' => str_repeat('p', 1025)], 'password'],
    'array email' => [['email' => ['a'], 'password' => 'x'], 'email'],
]);

it('logs out and invalidates the session', function (): void {
    $this->actingAs(Accounts::admin())->startSession();
    session()->put('marker', 'value');
    $before = session()->getId();
    $tokenBefore = csrf_token();

    $this->post('/logout', ['_token' => $tokenBefore])->assertRedirect('/login');

    $this->assertGuest();
    expect(session()->has('marker'))->toBeFalse()
        ->and(session()->getId())->not->toBe($before)
        ->and(csrf_token())->not->toBe($tokenBefore);
});

it('rejects logout via GET', function (): void {
    $this->actingAs(Accounts::admin())->get('/logout')->assertStatus(405);

    $this->assertAuthenticated();
});

it('redirects guests posting to logout to the login form', function (): void {
    $this->post('/logout')->assertRedirect('/login');
});

it('authenticates a user stored with a mixed-case email', function (): void {
    DB::table('users')->insert(['name' => 'Raw', 'email' => 'Mixed@Example.com', 'password' => Hash::make(Accounts::STRONG_PASSWORD), 'is_admin' => true]);

    $this->post('/login', ['email' => 'mixed@example.com', 'password' => Accounts::STRONG_PASSWORD])->assertRedirect('/');

    $this->assertAuthenticated();
});

it('rejects an overlong email at validation without attempting authentication', function (): void {
    Event::fake([Failed::class]);

    $this->post('/login', ['email' => str_repeat('a', 250).'@b.co', 'password' => Accounts::WRONG_PASSWORD])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->not->toBe(Accounts::GENERIC_ERROR);
    Event::assertNotDispatched(Failed::class);
});
