<?php

declare(strict_types=1);

use App\Http\Controllers\Controller;

arch('controllers do not use Eloquent or the database layer')
    ->expect('App\Http\Controllers')
    ->not->toUse([
        'Illuminate\Database',
        'App\Domain\Identity\Models',
        'App\Domain\Leads\Models',
        'App\Domain\Activities\Models',
    ]);

arch('controllers are final and extend the base controller')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toBeFinal()
    ->ignoring(Controller::class);

arch('controllers do not contain query logic')
    ->expect('App\Http\Controllers')
    ->not->toUse(['Illuminate\Support\Facades\DB', 'Illuminate\Database\Eloquent\Builder']);

arch('middleware classes are final')
    ->expect('App\Http\Middleware')
    ->classes()
    ->toBeFinal();

arch('form requests are final')
    ->expect('App\Http\Requests')
    ->classes()
    ->toBeFinal();
