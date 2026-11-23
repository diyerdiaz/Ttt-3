<?php

declare(strict_types=1);

use App\Application\Leads\DTOs\MoveLeadData;
use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Events\LeadStatusChanged;
use App\Domain\Leads\Exceptions\StaleBoardState;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\Support\InMemoryLeadStore;
use Tests\Support\LeadActions;

function boardWithTwoColumns(): InMemoryLeadStore
{
    $store = new InMemoryLeadStore;
    $store->seed(LeadStatus::New, 0);       // 1
    $store->seed(LeadStatus::New, 1);       // 2
    $store->seed(LeadStatus::New, 2);       // 3
    $store->seed(LeadStatus::Qualified, 0); // 4
    $store->seed(LeadStatus::Qualified, 1); // 5
    $store->calls = [];

    return $store;
}

it('reorders leads within the same column', function (): void {
    $store = boardWithTwoColumns();

    LeadActions::move($store)(new MoveLeadData(3, LeadStatus::New, [3, 1, 2]));

    expect($store->idsInColumn(LeadStatus::New))->toBe([3, 1, 2])
        ->and($store->idsInColumn(LeadStatus::Qualified))->toBe([4, 5]);
});

it('moves a lead to another column at the dropped index and updates its status', function (): void {
    $store = boardWithTwoColumns();

    LeadActions::move($store)(new MoveLeadData(2, LeadStatus::Qualified, [4, 2, 5]));

    expect($store->leads[2]->status)->toBe(LeadStatus::Qualified)
        ->and($store->idsInColumn(LeadStatus::Qualified))->toBe([4, 2, 5])
        ->and($store->leads[2]->position)->toBe(1);
});

it('leaves a gap but no collision in the source column', function (): void {
    $store = boardWithTwoColumns();

    LeadActions::move($store)(new MoveLeadData(2, LeadStatus::Qualified, [2, 4, 5]));

    $sourcePositions = array_map(static fn ($lead): int => $lead->position, $store->columns()['new']);
    expect($sourcePositions)->toBe([0, 2])
        ->and(array_unique($sourcePositions))->toHaveCount(2);
});

it('takes the lock, then the row, then writes the column, all inside the transaction', function (): void {
    $store = boardWithTwoColumns();

    LeadActions::move($store)(new MoveLeadData(2, LeadStatus::Qualified, [4, 2, 5]));

    expect($store->calls)->toBe(['begin', 'lock', 'find-for-update:2', 'place:qualified:4,2,5', 'end']);
});

it('rejects a stale ordering that does not match the column contents', function (array $ordered): void {
    $store = boardWithTwoColumns();

    expect(fn () => LeadActions::move($store)(new MoveLeadData(2, LeadStatus::Qualified, $ordered)))
        ->toThrow(StaleBoardState::class)
        ->and($store->leads[2]->status)->toBe(LeadStatus::New)
        ->and($store->idsInColumn(LeadStatus::Qualified))->toBe([4, 5]);
})->with([
    'a lead of the column is missing' => [[2, 4]],
    'a foreign lead is included' => [[4, 2, 5, 1]],
    'an unknown id is included' => [[4, 2, 5, 99]],
    'a duplicated id' => [[4, 2, 2, 5]],
    'the moved lead is absent' => [[4, 5]],
]);

it('does not write anything when the ordering is stale', function (): void {
    $store = boardWithTwoColumns();

    try {
        LeadActions::move($store)(new MoveLeadData(2, LeadStatus::Qualified, [4, 5]));
    } catch (StaleBoardState) {
    }

    expect(array_filter($store->calls, static fn (string $call): bool => str_starts_with($call, 'place:')))->toBe([]);
});

it('fails when the lead does not exist', function (): void {
    LeadActions::move(boardWithTwoColumns())(new MoveLeadData(99, LeadStatus::New, [99]));
})->throws(ModelNotFoundException::class);

it('dispatches LeadStatusChanged only on status change', function (): void {
    $store = boardWithTwoColumns();
    $log = [];
    $action = LeadActions::move($store, LeadActions::recordingDispatcher([LeadStatusChanged::class], $log));

    $action(new MoveLeadData(3, LeadStatus::New, [3, 1, 2]));
    expect($log)->toBe([]);

    $action(new MoveLeadData(3, LeadStatus::Won, [3]));
    expect($log)->toHaveCount(1)
        ->and($log[0]->leadId)->toBe(3)
        ->and($log[0]->from)->toBe(LeadStatus::New)
        ->and($log[0]->to)->toBe(LeadStatus::Won);
});

it('exposes the stale state as a domain exception', function (): void {
    expect(StaleBoardState::forColumn(LeadStatus::Won))->toBeInstanceOf(DomainException::class)
        ->and(StaleBoardState::forColumn(LeadStatus::Won)->getMessage())->toContain('Ganado');
});
