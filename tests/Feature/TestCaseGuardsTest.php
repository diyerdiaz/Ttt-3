<?php

declare(strict_types=1);

use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Http;

it('blocks real HTTP requests during tests', function (): void {
    Http::get('https://example.com');
})->throws(StrayRequestException::class);

it('switches the application environment and verifies it took effect', function (): void {
    expect($this->app->environment())->toBe('testing');

    $this->inEnvironment('production');

    expect($this->app->environment())->toBe('production')
        ->and($this->app->isProduction())->toBeTrue();
});
