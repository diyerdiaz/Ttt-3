<?php

declare(strict_types=1);

use App\Domain\Activities\Models\Activity;
use App\Domain\Activities\Policies\ActivityPolicy;
use App\Domain\Identity\Abilities;
use Illuminate\Support\Facades\Gate;
use Tests\Support\Accounts;

it('is registered as the policy of the Activity model', function (): void {
    expect(Gate::getPolicyFor(Activity::class))->toBeInstanceOf(ActivityPolicy::class);
});

it('lets admins view and log activities', function (string $ability): void {
    expect(Gate::forUser(Accounts::admin())->allows($ability, Activity::class))->toBeTrue();
})->with(['viewAny', 'create']);

it('denies non-admins and guests', function (string $ability): void {
    expect(Gate::forUser(Accounts::user())->allows($ability, Activity::class))->toBeFalse()
        ->and(Gate::forUser(null)->allows($ability, Activity::class))->toBeFalse();
})->with(['viewAny', 'create']);

it('delegates to the access-admin ability instead of duplicating the rule', function (): void {
    $admin = Accounts::admin();
    Gate::define(Abilities::ACCESS_ADMIN, static fn (): bool => false);

    expect(Gate::forUser($admin)->allows('create', Activity::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('viewAny', Activity::class))->toBeFalse();
});
