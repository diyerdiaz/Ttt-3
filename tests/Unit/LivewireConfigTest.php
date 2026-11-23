<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

it('generates class components only, under App\Http\Livewire', function (): void {
    expect(config('livewire.make_command.type'))->toBe('class')
        ->and(config('livewire.class_namespace'))->toBe('App\Http\Livewire')
        ->and(config('livewire.class_path'))->toBe(app_path('Http/Livewire'));
});

it('does not prefix generated components with an emoji', function (): void {
    expect(config('livewire.make_command.emoji'))->toBeFalse();
});

it('does not discover view-based (SFC/MFC) components', function (): void {
    expect(config('livewire.component_locations'))->toBe([])
        ->and(config('livewire.component_namespaces'))->toBe([]);
});

it('uses a layout view without a namespace hint, because no layouts namespace is registered', function (): void {
    expect(config('livewire.component_layout'))->not->toContain('::');
});
