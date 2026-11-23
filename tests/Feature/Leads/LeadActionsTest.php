<?php

declare(strict_types=1);

use App\Application\Leads\Actions\CreateLead;
use App\Application\Leads\Actions\UpdateLead;
use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Events\LeadCreated;
use App\Domain\Leads\Events\LeadStatusChanged;
use App\Domain\Leads\Events\LeadUpdated;
use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\ValueObjects\LeadData;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Tests\Support\LeadRows;

it('appends a new lead at the end of its status column', function (): void {
    Lead::factory()->status(LeadStatus::Contacted)->create(['position' => 0]);
    Lead::factory()->status(LeadStatus::Contacted)->create(['position' => 1]);
    Lead::factory()->status(LeadStatus::Won)->create(['position' => 9]);

    $lead = app(CreateLead::class)(new LeadData('Ada', status: LeadStatus::Contacted));

    expect($lead->fresh()?->position)->toBe(2)
        ->and(app(CreateLead::class)(new LeadData('Grace', status: LeadStatus::Contacted))->fresh()?->position)->toBe(3)
        ->and(app(CreateLead::class)(new LeadData('Edsger'))->fresh()?->position)->toBe(0);
});

it('assigns distinct positions to leads created concurrently in the same column', function (): void {
    foreach (range(1, 6) as $i) {
        app(CreateLead::class)(new LeadData("Lead {$i}"));
    }

    $positions = Lead::query()->where('status', 'new')->orderBy('position')->pluck('position')->all();

    expect($positions)->toBe([0, 1, 2, 3, 4, 5]);
    LeadRows::assertPositionsAreUnique();
});

it('moves a lead to the end of the new column when its status changes on edit', function (): void {
    Lead::factory()->status(LeadStatus::Proposal)->create(['position' => 0]);
    Lead::factory()->status(LeadStatus::Proposal)->create(['position' => 1]);
    $lead = Lead::factory()->status(LeadStatus::New)->create(['position' => 0]);

    $updated = app(UpdateLead::class)((int) $lead->id, new LeadData('Ada', status: LeadStatus::Proposal));

    expect($updated->fresh()?->status)->toBe(LeadStatus::Proposal)
        ->and($updated->fresh()?->position)->toBe(2);
    LeadRows::assertPositionsAreUnique();
});

it('keeps the position when the status does not change on edit', function (): void {
    $lead = Lead::factory()->status(LeadStatus::Qualified)->create(['position' => 5]);

    app(UpdateLead::class)((int) $lead->id, new LeadData('Renamed', status: LeadStatus::Qualified));

    expect($lead->fresh()?->position)->toBe(5)->and($lead->fresh()?->name)->toBe('Renamed');
});

it('dispatches LeadStatusChanged only when the status changes', function (): void {
    $lead = Lead::factory()->status(LeadStatus::New)->create();
    Event::fake([LeadCreated::class, LeadUpdated::class, LeadStatusChanged::class]);

    app(UpdateLead::class)((int) $lead->id, new LeadData('Renamed', status: LeadStatus::New));
    Event::assertDispatched(LeadUpdated::class, 1);
    Event::assertNotDispatched(LeadStatusChanged::class);

    app(UpdateLead::class)((int) $lead->id, new LeadData('Renamed', status: LeadStatus::Lost));
    Event::assertDispatched(
        LeadStatusChanged::class,
        static fn (LeadStatusChanged $e): bool => $e->leadId === $lead->id && $e->from === LeadStatus::New && $e->to === LeadStatus::Lost,
    );
    Event::assertDispatchedTimes(LeadStatusChanged::class, 1);
});

it('dispatches LeadCreated when a lead is created', function (): void {
    Event::fake([LeadCreated::class]);

    $lead = app(CreateLead::class)(new LeadData('Ada'));

    Event::assertDispatched(LeadCreated::class, static fn (LeadCreated $e): bool => $e->leadId === $lead->id);
});

it('fails to update a lead that does not exist', function (): void {
    expect(fn () => app(UpdateLead::class)(0, new LeadData('Ghost')))
        ->toThrow(ModelNotFoundException::class);
});
