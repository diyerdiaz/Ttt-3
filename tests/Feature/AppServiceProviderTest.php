<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

it('uses immutable dates', function (): void {
    expect(Date::now())->toBeInstanceOf(CarbonImmutable::class);
});

it('enables strict eloquent outside production', function (): void {
    expect(Model::preventsLazyLoading())->toBeTrue()
        ->and(Model::preventsSilentlyDiscardingAttributes())->toBeTrue()
        ->and(Model::preventsAccessingMissingAttributes())->toBeTrue();
});
