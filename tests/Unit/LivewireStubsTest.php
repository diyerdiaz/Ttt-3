<?php

declare(strict_types=1);

it('publishes Livewire class stubs with strict types and final classes', function (string $stub, bool $final): void {
    $contents = file_get_contents(dirname(__DIR__, 2).'/stubs/'.$stub);

    expect($contents)->toContain('declare(strict_types=1);');

    if ($final) {
        expect($contents)->toContain('final class');
    }
})->with([
    'component' => ['livewire.stub', true],
    'form' => ['livewire.form.stub', true],
    'attribute' => ['livewire.attribute.stub', true],
    'pest test' => ['livewire.pest.stub', false],
]);
