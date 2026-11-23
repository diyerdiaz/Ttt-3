<?php

declare(strict_types=1);

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Events\LeadCreated;
use App\Domain\Leads\ValueObjects\LeadData;
use App\Domain\Leads\ValueObjects\PhoneNumber;
use App\Domain\Shared\ValueObjects\EmailAddress;
use Tests\Support\InMemoryLeadStore;
use Tests\Support\LeadActions;

it('locks the board inside a transaction before choosing the position', function (): void {
    $store = new InMemoryLeadStore;

    LeadActions::create($store)(new LeadData('Ada'));

    expect($store->calls)->toBe(['begin', 'lock', 'next:new', 'create:new@0', 'end']);
});

it('puts the lead at the end of its status column', function (): void {
    $store = new InMemoryLeadStore;
    $store->seed(LeadStatus::Qualified, 0);
    $store->seed(LeadStatus::Qualified, 1);
    $store->seed(LeadStatus::Won, 5);

    $lead = LeadActions::create($store)(new LeadData('Ada', status: LeadStatus::Qualified));

    expect($lead->position)->toBe(2)
        ->and($lead->status)->toBe(LeadStatus::Qualified);
});

it('starts an empty column at position zero', function (): void {
    $store = new InMemoryLeadStore;
    $store->seed(LeadStatus::Won, 3);

    expect(LeadActions::create($store)(new LeadData('Ada'))->position)->toBe(0);
});

it('stores the normalized contact data', function (): void {
    $lead = LeadActions::create(new InMemoryLeadStore)(new LeadData(
        'Ada',
        EmailAddress::fromString(' ADA@Example.com '),
        PhoneNumber::fromString('+57  300 123 4567'),
        'Analytical Engines',
        'Met at a conference',
    ));

    expect($lead->email)->toBe('ada@example.com')
        ->and($lead->phone)->toBe('+57 300 123 4567')
        ->and($lead->company)->toBe('Analytical Engines')
        ->and($lead->notes)->toBe('Met at a conference');
});

it('dispatches LeadCreated with the new id', function (): void {
    $store = new InMemoryLeadStore;
    $log = [];

    $lead = LeadActions::create($store, LeadActions::recordingDispatcher([LeadCreated::class], $log))(new LeadData('Ada'));

    expect($log)->toHaveCount(1)
        ->and($log[0])->toBeInstanceOf(LeadCreated::class)
        ->and($log[0]->leadId)->toBe($lead->id);
});
