<?php

declare(strict_types=1);

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use App\Http\Livewire\Leads\LeadForm;
use Livewire\Exceptions\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Support\Accounts;

beforeEach(function (): void {
    $this->actingAs(Accounts::admin());
});

it('creates a lead from the form with minimal data', function (): void {
    Livewire::test(LeadForm::class)
        ->set('form.name', 'Ada Lovelace')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('leads.index'));

    $lead = Lead::query()->sole();

    expect($lead->name)->toBe('Ada Lovelace')
        ->and($lead->email)->toBeNull()
        ->and($lead->phone)->toBeNull()
        ->and($lead->status)->toBe(LeadStatus::New)
        ->and($lead->position)->toBe(0);
});

it('creates a lead with all its fields normalized and at the end of its column', function (): void {
    Lead::factory()->status(LeadStatus::Qualified)->create(['position' => 0]);

    Livewire::test(LeadForm::class)
        ->set('form.name', '  Grace Hopper ')
        ->set('form.email', ' Grace@Navy.TEST ')
        ->set('form.phone', ' +57  300 123 4567 ')
        ->set('form.company', 'US Navy')
        ->set('form.notes', 'Compiler pioneer')
        ->set('form.status', 'qualified')
        ->call('save')
        ->assertHasNoErrors();

    $lead = Lead::query()->where('name', 'Grace Hopper')->sole();

    expect($lead->email)->toBe('grace@navy.test')
        ->and($lead->phone)->toBe('+57 300 123 4567')
        ->and($lead->company)->toBe('US Navy')
        ->and($lead->notes)->toBe('Compiler pioneer')
        ->and($lead->status)->toBe(LeadStatus::Qualified)
        ->and($lead->position)->toBe(1);
});

it('stores blank optional fields as null', function (): void {
    Livewire::test(LeadForm::class)
        ->set('form.name', 'Ada')
        ->set('form.email', '')
        ->set('form.phone', '   ')
        ->set('form.company', '')
        ->set('form.notes', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(Lead::query()->sole()->only(['email', 'phone', 'company', 'notes']))
        ->toBe(['email' => null, 'phone' => null, 'company' => null, 'notes' => null]);
});

it('validates required name and email format', function (): void {
    Livewire::test(LeadForm::class)
        ->set('form.name', '   ')
        ->set('form.email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['form.name' => 'required', 'form.email' => 'email']);

    expect(Lead::query()->count())->toBe(0);
});

it('validates the other fields', function (string $field, string $value, string $rule): void {
    Livewire::test(LeadForm::class)
        ->set('form.name', 'Ada')
        ->set("form.{$field}", $value)
        ->call('save')
        ->assertHasErrors(["form.{$field}" => $rule]);

    expect(Lead::query()->count())->toBe(0);
})->with([
    'name too long' => ['name', str_repeat('a', 151), 'max'],
    'email too long' => ['email', str_repeat('a', 250).'@x.co', 'max'],
    'phone with letters' => ['phone', 'call me maybe', 'phone'],
    'phone too short' => ['phone', '123', 'phone'],
    'company too long' => ['company', str_repeat('a', 151), 'max'],
    'notes too long' => ['notes', str_repeat('a', 5001), 'max'],
    'unknown status' => ['status', 'archived', 'enum'],
]);

it('edits an existing lead', function (): void {
    $lead = Lead::factory()->status(LeadStatus::Contacted)->create(['name' => 'Old', 'position' => 3]);

    Livewire::test(LeadForm::class, ['lead' => $lead->id])
        ->assertSet('form.name', 'Old')
        ->assertSet('form.status', 'contacted')
        ->set('form.name', 'Renamed')
        ->set('form.company', 'Acme')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('leads.index'));

    expect($lead->fresh()?->name)->toBe('Renamed')
        ->and($lead->fresh()?->company)->toBe('Acme')
        ->and($lead->fresh()?->position)->toBe(3)
        ->and(Lead::query()->count())->toBe(1);
});

it('moves the lead to the end of the new column when its status is edited', function (): void {
    Lead::factory()->status(LeadStatus::Won)->create(['position' => 0]);
    $lead = Lead::factory()->status(LeadStatus::New)->create(['position' => 0]);

    Livewire::test(LeadForm::class, ['lead' => $lead->id])
        ->set('form.status', 'won')
        ->call('save')
        ->assertHasNoErrors();

    expect($lead->fresh()?->status)->toBe(LeadStatus::Won)->and($lead->fresh()?->position)->toBe(1);
});

it('responds 404 when editing a lead that does not exist', function (): void {
    $this->get('/leads/999999/edit')->assertNotFound();
});

it('does not let the client change which lead is being edited', function (): void {
    $lead = Lead::factory()->create();
    $other = Lead::factory()->create(['name' => 'Victim']);

    Livewire::test(LeadForm::class, ['lead' => $lead->id])->set('leadId', $other->id);
})->throws(CannotUpdateLockedPropertyException::class);

it('escapes lead data in the edit form', function (): void {
    $lead = Lead::factory()->create(['name' => '"><script>alert(1)</script>']);

    $this->get("/leads/{$lead->id}/edit")->assertOk()->assertDontSee('<script>alert(1)</script>', false);
});
