<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\Support\Accounts;
use Tests\Support\LivewirePayload;

it('rejects guests on the Livewire update endpoint', function (): void {
    $this->postJson(route('livewire.update', absolute: false), ['components' => []], ['X-Livewire' => 'true'])->assertUnauthorized();
});

it('rejects non admins on the Livewire update endpoint', function (): void {
    $this->actingAs(Accounts::user())
        ->postJson(route('livewire.update', absolute: false), ['components' => []], ['X-Livewire' => 'true'])
        ->assertForbidden();
});

it('answers a real round trip of the lead list for an admin', function (): void {
    $this->actingAs(Accounts::admin());
    $payload = LivewirePayload::leadIndex();

    $response = $this->postJson(route('livewire.update', absolute: false), $payload, ['X-Livewire' => 'true'])->assertOk();

    expect($response->json('components.0.effects.html'))->toBeString()->toContain('Buscar leads');
});

it('refuses the same round trip to a non admin', function (): void {
    $this->actingAs(Accounts::admin());
    $payload = LivewirePayload::leadIndex();
    $this->app['auth']->logout();

    $this->actingAs(Accounts::user())
        ->postJson(route('livewire.update', absolute: false), $payload, ['X-Livewire' => 'true'])
        ->assertForbidden();
});

it('enforces CSRF on the Livewire update endpoint', function (): void {
    $this->withCsrfEnforced()->actingAs(Accounts::admin());
    $payload = LivewirePayload::leadIndex();
    $uri = route('livewire.update', absolute: false);

    $this->postJson($uri, $payload, ['X-Livewire' => 'true'])->assertStatus(419);

    $this->withSession(['_token' => 'known-token'])
        ->postJson($uri, $payload, ['X-Livewire' => 'true', 'X-CSRF-TOKEN' => 'known-token'])
        ->assertOk();
});

it('rejects guests and non admins on the Livewire upload endpoint even with a valid signature', function (): void {
    $signed = URL::signedRoute('livewire.upload-file');

    expect(URL::hasValidSignature(Request::create($signed)))->toBeTrue();

    $this->postJson($signed)->assertUnauthorized();
    $this->actingAs(Accounts::user())->postJson($signed)->assertForbidden();
});

it('rejects guests and non admins on the Livewire preview endpoint even with a valid signature', function (): void {
    $signed = URL::signedRoute('livewire.preview-file', ['filename' => 'missing.png'], absolute: false);

    $this->getJson($signed)->assertUnauthorized();
    $this->actingAs(Accounts::user())->getJson($signed)->assertForbidden();
});

it('declares auth and admin on every Livewire endpoint that has side effects or serves files', function (string $routeName): void {
    $middleware = app('router')->gatherRouteMiddleware(app('router')->getRoutes()->getByName($routeName));

    expect($middleware)->toContain(Authenticate::class)->toContain(EnsureUserIsAdmin::class);
})->with(['livewire.update', 'livewire.upload-file', 'livewire.preview-file']);
