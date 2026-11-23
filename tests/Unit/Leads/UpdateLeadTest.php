<?php

declare(strict_types=1);

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Events\LeadStatusChanged;
use App\Domain\Leads\Events\LeadUpdated;
use App\Domain\Leads\ValueObjects\LeadData;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\Support\InMemoryLeadStore;
use Tests\Support\LeadActions;

it('updates the lead keeping its position when the status does not change', function (): void {
    $store = new InMemoryLeadStore;
    $lead = $store->seed(LeadStatus::Contacted, 4);
    $store->calls = [];

    $updated = LeadActions::update($store)((int) $lead->id, new LeadData('Grace', company: 'Navy', status: LeadStatus::Contacted));

    expect($updated->name)->toBe('Grace')
        ->and($updated->company)->toBe('Navy')
        ->and($updated->position)->toBe(4)
        ->and($store->calls)->toBe(['begin', 'lock', 'update:keep', 'end']);
});

it('moves the lead to the end of the new column when its status changes', function (): void {
    $store = new InMemoryLeadStore;
    $store->seed(LeadStatus::Proposal, 0);
    $store->seed(LeadStatus::Proposal, 1);
    $lead = $store->seed(LeadStatus::New, 0);

    $updated = LeadActions::update($store)((int) $lead->id, new LeadData('Ada', status: LeadStatus::Proposal));

    expect($updated->status)->toBe(LeadStatus::Proposal)
        ->and($updated->position)->toBe(2);
});

it('dispatches LeadStatusChanged only when the status changes', function (): void {
    $store = new InMemoryLeadStore;
    $lead = $store->seed(LeadStatus::New, 0);
    $log = [];
    $events = LeadActions::recordingDispatcher([LeadUpdated::class, LeadStatusChanged::class], $log);
    $action = LeadActions::update($store, $events);

    $action((int) $lead->id, new LeadData('Ada renamed', status: LeadStatus::New));
    expect($log)->toHaveCount(1)->and($log[0])->toBeInstanceOf(LeadUpdated::class);

    $action((int) $lead->id, new LeadData('Ada renamed', status: LeadStatus::Won));
    expect($log)->toHaveCount(3)
        ->and($log[1])->toBeInstanceOf(LeadUpdated::class)
        ->and($log[2])->toBeInstanceOf(LeadStatusChanged::class)
        ->and($log[2]->from)->toBe(LeadStatus::New)
        ->and($log[2]->to)->toBe(LeadStatus::Won)
        ->and($log[2]->leadId)->toBe($lead->id);
});

it('fails when the lead does not exist', function (): void {
    LeadActions::update(new InMemoryLeadStore)(99, new LeadData('Ada'));
})->throws(ModelNotFoundException::class);

it('keeps the current status when the data does not specify one', function (): void {
    $store = new InMemoryLeadStore;
    $lead = $store->seed(LeadStatus::Won, 3);
    $log = [];
    $events = LeadActions::recordingDispatcher([LeadUpdated::class, LeadStatusChanged::class], $log);

    $updated = LeadActions::update($store, $events)((int) $lead->id, new LeadData('Renamed'));

    expect($updated->status)->toBe(LeadStatus::Won)
        ->and($updated->position)->toBe(3)
        ->and($updated->name)->toBe('Renamed')
        ->and($log)->toHaveCount(1)->and($log[0])->toBeInstanceOf(LeadUpdated::class);
});

it('does not dispatch LeadUpdated when nothing changed', function (): void {
    $store = new InMemoryLeadStore;
    $lead = $store->seed(LeadStatus::New, 0);
    $log = [];
    $events = LeadActions::recordingDispatcher([LeadUpdated::class, LeadStatusChanged::class], $log);
    $action = LeadActions::update($store, $events);

    $action((int) $lead->id, new LeadData('Same'));
    $log = [];
    $action((int) $lead->id, new LeadData('Same'));

    expect($log)->toBe([]);
});

it('allows every pair of statuses under the current rules', function (LeadStatus $from, LeadStatus $to): void {
    $store = new InMemoryLeadStore;
    $lead = $store->seed($from, 0);

    expect(LeadActions::update($store)((int) $lead->id, new LeadData('Ada', status: $to))->status)->toBe($to);
})->with(fn (): Generator => (function (): Generator {
    foreach (LeadStatus::cases() as $from) {
        foreach (LeadStatus::cases() as $to) {
            yield "{$from->value} to {$to->value}" => [$from, $to];
        }
    }
})());
