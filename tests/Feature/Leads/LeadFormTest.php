<?php

declare(strict_types=1);

use App\Application\Leads\Actions\UpdateLead;
use App\Domain\Leads\Contracts\LeadRepository;
use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Events\LeadStatusChanged;
use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\ValueObjects\LeadData;
use App\Http\Livewire\Leads\LeadForm;
use App\Infrastructure\Persistence\EloquentLeadRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\Rules\Enum;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Support\Accounts;
use Tests\Support\RefusingTransitionRepository;

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

it('validates the other fields with the rule that applies', function (string $field, string $value, string $rule): void {
    Livewire::test(LeadForm::class)
        ->set('form.name', 'Ada')
        ->set("form.{$field}", $value)
        ->call('save')
        ->assertHasErrors(["form.{$field}" => $rule]);

    expect(Lead::query()->count())->toBe(0);
})->with([
    'name too long' => ['name', str_repeat('a', 151), 'max'],
    'company too long' => ['company', str_repeat('a', 151), 'max'],
    'notes too long' => ['notes', str_repeat('a', 5001), 'max'],
    'unknown status' => ['status', 'archived', Enum::class],
]);

it('reports the length rule for an email longer than the column, whatever else PHP says about its format', function (): void {
    // PHP's own email filter already refuses addresses over 254 characters, so this input fails both rules;
    // asserting the `max` key is what proves the length rule exists (and the column limit is enforced).
    $email = str_repeat('a', 64).'@'.implode('.', array_fill(0, 4, str_repeat('b', 60))).'.test';

    expect(strlen($email))->toBeGreaterThan(254);

    Livewire::test(LeadForm::class)
        ->set('form.name', 'Ada')
        ->set('form.email', $email)
        ->call('save')
        ->assertHasErrors(['form.email' => 'max']);
});

it('rejects phones that are not valid after normalisation', function (string $phone): void {
    Livewire::test(LeadForm::class)
        ->set('form.name', 'Ada')
        ->set('form.phone', $phone)
        ->call('save')
        ->assertHasErrors('form.phone')
        ->assertSee('teléfono no es válido');

    expect(Lead::query()->count())->toBe(0);
})->with([
    'letters' => ['call me maybe'],
    'too few digits' => ['123'],
    'longer than 32 once normalised' => ['+57 300 123 4567 890 123 4567 8901'],
]);

it('applies the phone length limit after collapsing repeated spaces', function (): void {
    $raw = '+57'.str_repeat(' ', 12).'300'.str_repeat(' ', 12).'123 4567';

    expect(strlen($raw))->toBeGreaterThan(32);

    Livewire::test(LeadForm::class)
        ->set('form.name', 'Ada')
        ->set('form.phone', $raw)
        ->call('save')
        ->assertHasNoErrors();

    expect(Lead::query()->sole()->phone)->toBe('+57 300 123 4567');
});

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

it('escapes lead data in the rendered edit form', function (): void {
    $lead = Lead::factory()->create(['name' => '"><script>alert(1)</script>']);

    $response = $this->get("/leads/{$lead->id}/edit")->assertOk();

    // The name only reaches the HTML through the Livewire snapshot: it must be there, and escaped.
    $response->assertSee('&lt;script&gt;alert(1)', false)->assertDontSee('<script>alert(1)', false);
});

it('does not lose a status change made after the form was opened', function (): void {
    $lead = Lead::factory()->status(LeadStatus::New)->create(['position' => 0]);
    $form = Livewire::test(LeadForm::class, ['lead' => $lead->id]);
    $changes = [];
    Event::listen(LeadStatusChanged::class, static function (LeadStatusChanged $event) use (&$changes): void {
        $changes[] = $event;
    });

    app(UpdateLead::class)((int) $lead->id, new LeadData('Old', status: LeadStatus::Won));

    $form->set('form.company', 'Acme')->call('save')->assertHasNoErrors();

    expect($lead->fresh()?->status)->toBe(LeadStatus::Won)
        ->and($lead->fresh()?->company)->toBe('Acme')
        ->and($changes)->toHaveCount(1);
});

it('moves the lead when the user changes the status away from the one loaded', function (): void {
    $lead = Lead::factory()->status(LeadStatus::New)->create(['position' => 0]);

    Livewire::test(LeadForm::class, ['lead' => $lead->id])
        ->set('form.status', 'lost')
        ->call('save')
        ->assertHasNoErrors();

    expect($lead->fresh()?->status)->toBe(LeadStatus::Lost);
});

it('does not let the client rewrite the status the form was loaded with', function (): void {
    $lead = Lead::factory()->create();

    Livewire::test(LeadForm::class, ['lead' => $lead->id])->set('originalStatus', 'won');
})->throws(CannotUpdateLockedPropertyException::class);

it('shows a readable error instead of failing when the status transition is refused', function (): void {
    $lead = Lead::factory()->status(LeadStatus::Won)->create();
    app()->bind(LeadRepository::class, static fn (): LeadRepository => new RefusingTransitionRepository(new EloquentLeadRepository));

    Livewire::test(LeadForm::class, ['lead' => $lead->id])
        ->set('form.status', 'new')
        ->call('save')
        ->assertHasErrors('form.status')
        ->assertSee('Ganado')
        ->assertNoRedirect();

    expect($lead->fresh()?->status)->toBe(LeadStatus::Won);
});

it('answers 422 to values of the wrong type instead of failing', function (string $field, mixed $value): void {
    Livewire::test(LeadForm::class)->set('form.name', 'Ada')->set("form.{$field}", $value)->assertStatus(422);
})->with([
    'array name' => ['name', ['x']],
    'array email' => ['email', ['x']],
    'array notes' => ['notes', ['x']],
    'array status' => ['status', ['x']],
]);

it('answers 422 to nested paths and nulls on the text fields', function (string $path, mixed $value): void {
    Livewire::test(LeadForm::class)->set($path, $value)->assertStatus(422);
})->with([
    'null name' => ['form.name', null],
    'nested index on name' => ['form.name.0', 'x'],
    'nested key on notes' => ['form.notes.a.b', 'x'],
]);

it('stores text cleaned of control characters and invalid UTF-8', function (): void {
    Livewire::test(LeadForm::class)
        ->set('form.name', "Ada\0 Love\x07lace\nII")
        ->set('form.company', "Ana\xC3lytical")
        ->set('form.notes', "line 1\r\nline\t2\0\x01")
        ->call('save')
        ->assertHasNoErrors();

    $lead = Lead::query()->sole();

    expect($lead->name)->toBe('Ada Lovelace II')
        ->and(mb_check_encoding((string) $lead->company, 'UTF-8'))->toBeTrue()
        ->and($lead->notes)->toBe("line 1\nline\t2");
});

it('does not fail on a name made only of control characters', function (): void {
    Livewire::test(LeadForm::class)->set('form.name', "\0\x07")->call('save')->assertHasErrors(['form.name' => 'required']);

    expect(Lead::query()->count())->toBe(0);
});

it('rejects lead ids that are not plain numbers or do not fit an integer', function (string $uri): void {
    $this->get($uri)->assertNotFound();
})->with(['/leads/99999999999999999999/edit', '/leads/1e3/edit', '/leads/-1/edit', '/leads/0x1/edit']);

it('answers 422 to updates that target the form object itself instead of a field', function (string $path, mixed $value): void {
    Livewire::test(LeadForm::class)->set($path, $value)->assertStatus(422);
})->with([
    'scalar over the form' => ['form', 'str'],
    'unknown field' => ['form.nope', 'x'],
]);

it('rejects a name made only of Unicode separators', function (string $name): void {
    Livewire::test(LeadForm::class)->set('form.name', $name)->call('save')->assertHasErrors(['form.name' => 'required']);

    expect(Lead::query()->count())->toBe(0);
})->with([
    'no-break space' => ["\u{00A0}"],
    'em space and ideographic space' => ["\u{2003}\u{3000}"],
    'line separator' => ["\u{2028}"],
]);

it('expands an array sent over the form into its allowed fields instead of failing', function (): void {
    Livewire::test(LeadForm::class)->set('form', ['name' => 'Ada'])->assertOk()->assertSet('form.name', 'Ada');
});
