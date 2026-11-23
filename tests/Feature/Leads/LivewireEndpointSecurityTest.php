<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Auth\Middleware\Authenticate;
use Tests\Support\Accounts;

it('rejects guests on the Livewire update endpoint', function (): void {
    $this->postJson(route('livewire.update', absolute: false), ['components' => []], ['X-Livewire' => 'true'])->assertUnauthorized();
});

it('rejects non admins on the Livewire update endpoint', function (): void {
    $this->actingAs(Accounts::user())
        ->postJson(route('livewire.update', absolute: false), ['components' => []], ['X-Livewire' => 'true'])
        ->assertForbidden();
});

it('lets admins reach the Livewire update endpoint', function (): void {
    $this->actingAs(Accounts::admin())
        ->postJson(route('livewire.update', absolute: false), ['components' => []], ['X-Livewire' => 'true'])
        ->assertSuccessful();
});

it('rejects guests and non admins on the Livewire upload endpoint', function (): void {
    $uri = route('livewire.upload-file', absolute: false);

    $this->postJson($uri)->assertUnauthorized();
    $this->actingAs(Accounts::user())->postJson($uri)->assertForbidden();
});

it('declares auth and admin on both Livewire endpoints', function (string $routeName): void {
    $middleware = app('router')->gatherRouteMiddleware(app('router')->getRoutes()->getByName($routeName));

    expect($middleware)->toContain(Authenticate::class)->toContain(EnsureUserIsAdmin::class);
})->with(['livewire.update', 'livewire.upload-file']);
