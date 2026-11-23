<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

arch()->preset()->php();
arch()->preset()->security();

arch('app uses strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('domain does not depend on outer layers or the web stack')
    ->expect('App\Domain')
    ->not->toUse([
        'App\Application',
        'App\Http',
        'App\Infrastructure',
        'Illuminate\Http',
        'Livewire',
    ]);

arch('application does not depend on http or infrastructure')
    ->expect('App\Application')
    ->not->toUse([
        'App\Http',
        'App\Infrastructure',
        'Illuminate\Http',
        'Livewire',
    ]);

arch('http does not depend on infrastructure')
    ->expect('App\Http')
    ->not->toUse('App\Infrastructure');

arch('http does not use the DB facade')
    ->expect('App\Http')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('application does not use the DB facade')
    ->expect('App\Application')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('form requests extend FormRequest')
    ->expect('App\Http\Requests')
    ->toExtend(FormRequest::class);

arch('application classes are final')
    ->expect('App\Application')
    ->classes()
    ->toBeFinal();

arch('infrastructure classes are final')
    ->expect('App\Infrastructure')
    ->classes()
    ->toBeFinal();

it('keeps every model in the domain layer', function (): void {
    expect(is_dir(dirname(__DIR__, 2).'/app/Models'))->toBeFalse();
});

arch('application does not hold Eloquent models')
    ->expect('App\Application')
    ->not->toExtend(Model::class);

arch('http does not hold Eloquent models')
    ->expect('App\Http')
    ->not->toExtend(Model::class);

arch('infrastructure does not hold Eloquent models')
    ->expect('App\Infrastructure')
    ->not->toExtend(Model::class);

it('has no app/Livewire directory', function (): void {
    expect(is_dir(dirname(__DIR__, 2).'/app/Livewire'))->toBeFalse();
});
