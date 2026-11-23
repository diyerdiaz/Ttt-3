<?php

declare(strict_types=1);

it('publishes only stubs that Livewire actually reads', function (): void {
    $readByLivewire = collect(glob(base_path('vendor/livewire/livewire/src/Features/SupportConsoleCommands/Commands/*Command.php')) ?: [])
        ->reject(fn (string $file): bool => basename($file) === 'StubsCommand.php') // lists every stub it can publish, not the ones generators read
        ->flatMap(fn (string $file): array => preg_match_all('/(livewire[\w.-]*\.stub)/', (string) file_get_contents($file), $m) ? $m[1] : [])
        ->unique();

    $published = collect(glob(base_path('stubs/*.stub')) ?: [])->map(basename(...));

    expect($published)->not->toBeEmpty()
        ->and($published->diff($readByLivewire)->values()->all())->toBe([]);
});

it('publishes Livewire stubs with strict types and final classes', function (string $stub, bool $final): void {
    $contents = (string) file_get_contents(base_path('stubs/'.$stub));

    expect($contents)->toContain('declare(strict_types=1);');

    if ($final) {
        expect($contents)->toContain('final class');
    }
})->with([
    'component' => ['livewire.stub', true],
    'form' => ['livewire.form.stub', true],
    'attribute' => ['livewire.attribute.stub', true],
    'test' => ['livewire-mfc-test.stub', false],
]);

it('does not leave unused imports in the form stub', function (): void {
    expect(file_get_contents(base_path('stubs/livewire.form.stub')))->not->toContain('Attributes\Validate');
});
