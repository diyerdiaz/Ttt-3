<?php

declare(strict_types=1);

arch('app uses strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('domain does not depend on HTTP layer')
    ->expect('App\Domain')
    ->not->toUse('Illuminate\Http');

arch('domain does not depend on infrastructure')
    ->expect('App\Domain')
    ->not->toUse('App\Infrastructure');

arch('controllers do not use the DB facade')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');
