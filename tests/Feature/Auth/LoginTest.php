<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Auth;

const GENERIC_ERROR = 'Credenciales inválidas';

function adminUser(string $email = 'ada@example.com'): User
{
    return User::factory()->admin()->create(['email' => $email, 'password' => 'correct-horse-battery']);
}

it('shows the login form to guests', function (): void {
    $this->get('/login')
        ->assertOk()
        ->assertSee('name="_token"', false)
        ->assertSee('action="'.route('login').'"', false)
        ->assertSee('type="password"', false);
});

it('redirects authenticated users away from the login form', function (): void {
    $this->actingAs(adminUser())->get('/login')->assertRedirect('/');
});

it('logs in an admin with valid credentials and regenerates the session', function (): void {
    adminUser();

    $this->startSession();
    $before = session()->getId();
    $token = csrf_token();

    $this->post('/login', ['_token' => $token, 'email' => 'ada@example.com', 'password' => 'correct-horse-battery'])
        ->assertRedirect('/');

    $this->assertAuthenticated();
    expect(session()->getId())->not->toBe($before);
});

it('redirects to the intended url after login', function (): void {
    adminUser();

    $this->get('/')->assertRedirect('/login');
    $this->post('/login', ['email' => 'ada@example.com', 'password' => 'correct-horse-battery'])->assertRedirect('/');
});

it('treats the email case-insensitively when authenticating', function (): void {
    adminUser('ada@example.com');

    $this->post('/login', ['email' => '  ADA@Example.COM ', 'password' => 'correct-horse-battery'])
        ->assertRedirect('/');

    $this->assertAuthenticated();
});

it('rejects invalid credentials with a generic error', function (): void {
    adminUser();

    $this->from('/login')
        ->post('/login', ['email' => 'ada@example.com', 'password' => 'wrong-password-123'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['email' => GENERIC_ERROR])
        ->assertSessionHasInput('email', 'ada@example.com')
        ->assertSessionMissing('_old_input.password');

    $this->assertGuest();
});

it('does not reveal whether a user exists', function (): void {
    adminUser();

    $wrongPassword = $this->post('/login', ['email' => 'ada@example.com', 'password' => 'wrong-password-123'])
        ->assertSessionHasErrors('email');
    $messageForKnown = session('errors')->first('email');

    $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password-123'])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toBe($messageForKnown)->toBe(GENERIC_ERROR);
    $wrongPassword->assertRedirect();
});

it('rejects a non-admin user with valid credentials without creating a session', function (): void {
    User::factory()->create(['email' => 'user@example.com', 'password' => 'correct-horse-battery']);

    $this->post('/login', ['email' => 'user@example.com', 'password' => 'correct-horse-battery'])
        ->assertSessionHasErrors(['email' => GENERIC_ERROR]);

    $this->assertGuest();
    expect(Auth::user())->toBeNull();
});

it('validates that email and password are present', function (array $payload, string $field): void {
    $this->post('/login', $payload)->assertSessionHasErrors($field);
    $this->assertGuest();
})->with([
    'missing email' => [['password' => 'x'], 'email'],
    'missing password' => [['email' => 'ada@example.com'], 'password'],
    'array email' => [['email' => ['a'], 'password' => 'x'], 'email'],
]);

it('logs out and invalidates the session', function (): void {
    $this->actingAs(adminUser())->startSession();
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
    $this->actingAs(adminUser())->get('/logout')->assertStatus(405);

    $this->assertAuthenticated();
});

it('redirects guests posting to logout to the login form', function (): void {
    $this->post('/logout')->assertRedirect('/login');
});
