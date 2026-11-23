<?php

declare(strict_types=1);

use App\Domain\Identity\Abilities;
use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\Policies\LeadPolicy;
use App\Http\Livewire\Leads\LeadForm;
use App\Http\Livewire\Leads\LeadIndex;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\Support\Accounts;

it('is registered as the policy of the Lead model', function (): void {
    expect(Gate::getPolicyFor(Lead::class))->toBeInstanceOf(LeadPolicy::class);
});

it('lets admins do everything with leads', function (string $ability): void {
    $subject = in_array($ability, ['viewAny', 'create', 'export'], true) ? Lead::class : Lead::factory()->make();

    expect(Gate::forUser(Accounts::admin())->allows($ability, $subject))->toBeTrue();
})->with(['viewAny', 'create', 'view', 'update', 'export', 'move']);

it('denies every ability to non-admins and guests', function (string $ability): void {
    $lead = Lead::factory()->make();
    $subject = in_array($ability, ['viewAny', 'create', 'export'], true) ? Lead::class : $lead;

    expect(Gate::forUser(Accounts::user())->allows($ability, $subject))->toBeFalse()
        ->and(Gate::forUser(null)->allows($ability, $subject))->toBeFalse();
})->with(['viewAny', 'create', 'view', 'update', 'export', 'move']);

it('delegates to the access-admin ability instead of duplicating the rule', function (): void {
    $admin = Accounts::admin();
    Gate::define(Abilities::ACCESS_ADMIN, static fn (): bool => false);

    expect(Gate::forUser($admin)->allows('viewAny', Lead::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('update', Lead::factory()->make()))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('export', Lead::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('move', Lead::factory()->make()))->toBeFalse();
});

it('is consulted by the lead components', function (): void {
    $this->actingAs(Accounts::admin());
    $lead = Lead::factory()->create();
    $asked = [];
    Gate::before(static function (mixed $user, string $ability) use (&$asked): null {
        $asked[] = $ability;

        return null;
    });

    Livewire::test(LeadIndex::class);
    expect($asked)->toContain('viewAny');

    $asked = [];
    Livewire::test(LeadForm::class)->set('form.name', 'Ada')->call('save');
    expect($asked)->toContain('create');

    $asked = [];
    Livewire::test(LeadForm::class, ['lead' => $lead->id])->set('form.name', 'Renamed')->call('save');
    expect($asked)->toContain('update');
});
