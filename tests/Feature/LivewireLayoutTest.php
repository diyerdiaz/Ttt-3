<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tests\Fixtures\Livewire\FullPageComponent;

it('renders a full-page component without an explicit #[Layout]', function (): void {
    Route::get('/__fixtures/full-page', FullPageComponent::class);

    $this->get('/__fixtures/full-page')
        ->assertOk()
        ->assertSee('fixture-full-page');
});
