<?php

declare(strict_types=1);
use Tests\Support\Accounts;

it('rejects a login post without a CSRF token once the testing bypass is off', function (): void {
    $this->withCsrfEnforced();

    $this->post('/login', ['email' => 'ada@example.com', 'password' => 'whatever-password'])->assertStatus(419);
});

it('rejects a logout post without a CSRF token once the testing bypass is off', function (): void {
    $this->withCsrfEnforced()->actingAs(Accounts::admin());

    $this->post('/logout')->assertStatus(419);
});

it('accepts a login post carrying the session CSRF token', function (): void {
    $this->withCsrfEnforced();

    $this->withSession(['_token' => 'known-token'])
        ->post('/login', ['_token' => 'known-token', 'email' => 'ada@example.com', 'password' => 'whatever-password'])
        ->assertSessionHasErrors('email');
});
