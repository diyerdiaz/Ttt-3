<?php

declare(strict_types=1);

use App\Application\Leads\Actions\MoveLead;
use App\Application\Leads\DTOs\MoveLeadData;
use App\Domain\Leads\Contracts\LeadBoardRepository;
use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Events\LeadStatusChanged;
use App\Domain\Leads\Exceptions\StaleBoardState;
use App\Domain\Leads\Models\Lead;
use App\Domain\Shared\Contracts\TransactionManager;
use App\Infrastructure\Persistence\EloquentLeadRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Support\FailsAfterCallbackTransactionManager;
use Tests\Support\LeadRows;
use Tests\Support\QueryCounter;

/**
 * @return array<int, Lead> leads keyed by their 1-based creation order
 */
function seedBoard(): array
{
    $leads = [];
    foreach ([[LeadStatus::New, 0], [LeadStatus::New, 1], [LeadStatus::New, 2], [LeadStatus::Qualified, 0], [LeadStatus::Qualified, 1]] as $i => [$status, $position]) {
        $leads[$i + 1] = Lead::factory()->status($status)->create(['position' => $position]);
    }

    return $leads;
}

/**
 * @param  list<Lead>  $leads
 * @return list<int>
 */
function ids(array $leads): array
{
    return array_map(static fn (Lead $lead): int => (int) $lead->id, $leads);
}

it('persists a reorder within a column with dense positions', function (): void {
    $leads = seedBoard();

    app(MoveLead::class)(new MoveLeadData($leads[3]->id, LeadStatus::New, [$leads[3]->id, $leads[1]->id, $leads[2]->id]));

    expect(app(LeadBoardRepository::class)->idsInColumn(LeadStatus::New))->toBe([$leads[3]->id, $leads[1]->id, $leads[2]->id])
        ->and(Lead::query()->where('status', 'new')->orderBy('position')->pluck('position')->all())->toBe([0, 1, 2]);
    LeadRows::assertPositionsAreUnique();
});

it('moves a lead to another column at the dropped index and leaves a gap but no collision behind', function (): void {
    $leads = seedBoard();

    app(MoveLead::class)(new MoveLeadData($leads[1]->id, LeadStatus::Qualified, [$leads[4]->id, $leads[1]->id, $leads[5]->id]));

    expect($leads[1]->fresh()?->status)->toBe(LeadStatus::Qualified)
        ->and(Lead::query()->where('status', 'qualified')->orderBy('position')->pluck('id')->all())->toBe([$leads[4]->id, $leads[1]->id, $leads[5]->id])
        ->and(Lead::query()->where('status', 'new')->orderBy('position')->pluck('position')->all())->toBe([1, 2]);
    LeadRows::assertPositionsAreUnique();
});

it('moves a lead into an empty column', function (): void {
    $leads = seedBoard();

    app(MoveLead::class)(new MoveLeadData($leads[2]->id, LeadStatus::Won, [$leads[2]->id]));

    expect($leads[2]->fresh()?->status)->toBe(LeadStatus::Won)->and($leads[2]->fresh()?->position)->toBe(0);
});

it('only rewrites the rows whose status or position changed', function (): void {
    $leads = seedBoard();
    Lead::query()->update(['updated_at' => '2020-01-01 00:00:00+00']);

    app(MoveLead::class)(new MoveLeadData($leads[3]->id, LeadStatus::New, [$leads[1]->id, $leads[2]->id, $leads[3]->id]));
    expect(Lead::query()->where('updated_at', '>', '2021-01-01')->count())->toBe(0);

    app(MoveLead::class)(new MoveLeadData($leads[3]->id, LeadStatus::New, [$leads[1]->id, $leads[3]->id, $leads[2]->id]));
    expect(Lead::query()->where('updated_at', '>', '2021-01-01')->pluck('id')->sort()->values()->all())->toBe([$leads[2]->id, $leads[3]->id]);
});

it('rewrites the destination column with a single UPDATE statement', function (): void {
    $leads = seedBoard();
    $ids = [$leads[4]->id, $leads[1]->id, $leads[5]->id];

    DB::enableQueryLog();
    app(LeadBoardRepository::class)->placeColumn(LeadStatus::Qualified, $ids);
    $updates = array_filter(DB::getQueryLog(), static fn (array $q): bool => str_starts_with(strtolower((string) $q['query']), 'update'));
    DB::disableQueryLog();

    expect($updates)->toHaveCount(1)
        ->and(strtolower((string) array_values($updates)[0]['query']))->toContain('with ordinality');
});

it('rejects a stale ordering and leaves the board untouched', function (): void {
    $leads = seedBoard();
    $before = Lead::query()->orderBy('id')->get(['id', 'status', 'position'])->toArray();

    expect(fn () => app(MoveLead::class)(new MoveLeadData($leads[1]->id, LeadStatus::Qualified, [$leads[1]->id, $leads[4]->id])))
        ->toThrow(StaleBoardState::class);

    expect(Lead::query()->orderBy('id')->get(['id', 'status', 'position'])->toArray())->toBe($before);
});

it('rolls back all position changes when the move fails', function (): void {
    $leads = seedBoard();
    $before = Lead::query()->orderBy('id')->get(['id', 'status', 'position'])->toArray();
    $repository = app(EloquentLeadRepository::class);
    $move = new MoveLead($repository, new FailsAfterCallbackTransactionManager(app(TransactionManager::class)), app('events'));

    expect(fn () => $move(new MoveLeadData($leads[1]->id, LeadStatus::Qualified, [$leads[4]->id, $leads[1]->id, $leads[5]->id])))
        ->toThrow(RuntimeException::class);

    expect(Lead::query()->orderBy('id')->get(['id', 'status', 'position'])->toArray())->toBe($before);
});

it('dispatches LeadStatusChanged after commit only on status change', function (): void {
    $leads = seedBoard();
    $received = [];
    Event::listen(LeadStatusChanged::class, static function (LeadStatusChanged $event) use (&$received): void {
        $received[] = $event;
    });

    app(MoveLead::class)(new MoveLeadData($leads[3]->id, LeadStatus::New, [$leads[3]->id, $leads[1]->id, $leads[2]->id]));
    expect($received)->toBe([]);

    DB::beginTransaction();
    app(MoveLead::class)(new MoveLeadData($leads[1]->id, LeadStatus::Qualified, [$leads[4]->id, $leads[1]->id, $leads[5]->id]));
    expect($received)->toBe([]);
    DB::commit();

    expect($received)->toHaveCount(1)
        ->and($received[0]->leadId)->toBe($leads[1]->id)
        ->and($received[0]->from)->toBe(LeadStatus::New)
        ->and($received[0]->to)->toBe(LeadStatus::Qualified);
});

it('drops the event when the move rolls back', function (): void {
    $leads = seedBoard();
    $received = [];
    Event::listen(LeadStatusChanged::class, static function (LeadStatusChanged $event) use (&$received): void {
        $received[] = $event;
    });
    $repository = app(EloquentLeadRepository::class);
    $move = new MoveLead($repository, new FailsAfterCallbackTransactionManager(app(TransactionManager::class)), app('events'));

    expect(fn () => $move(new MoveLeadData($leads[1]->id, LeadStatus::Won, [$leads[1]->id])))->toThrow(RuntimeException::class)
        ->and($received)->toBe([]);
});

describe('board repository', function (): void {
    it('returns every status in board order, with its leads ordered by position', function (): void {
        $a = Lead::factory()->status(LeadStatus::Won)->create(['position' => 5]);
        $b = Lead::factory()->status(LeadStatus::Won)->create(['position' => 2]);
        $c = Lead::factory()->status(LeadStatus::New)->create(['position' => 0]);

        $columns = app(LeadBoardRepository::class)->columns();

        expect(array_keys($columns))->toBe(array_map(static fn (LeadStatus $s): string => $s->value, LeadStatus::boardOrder()))
            ->and(ids($columns['won']))->toBe([$b->id, $a->id])
            ->and(ids($columns['new']))->toBe([$c->id])
            ->and($columns['lost'])->toBe([]);
    });

    it('loads the board with one query and only the card columns', function (): void {
        Lead::factory()->count(3)->create();
        $captured = [];

        $count = QueryCounter::during(static function () use (&$captured): void {
            app(LeadBoardRepository::class)->columns();
            $captured = DB::getQueryLog();
        });

        expect($count)->toBe(1)
            ->and((string) $captured[0]['query'])->not->toContain('notes')
            ->and((string) $captured[0]['query'])->not->toContain('*');
    });

    it('locks the row it returns for update', function (): void {
        $lead = Lead::factory()->create();

        DB::enableQueryLog();
        app(LeadBoardRepository::class)->findForUpdate((int) $lead->id);
        $sql = strtolower((string) DB::getQueryLog()[0]['query']);
        DB::disableQueryLog();

        expect($sql)->toContain('for update');
    });

    it('lists the ids of a column in position order', function (): void {
        $a = Lead::factory()->status(LeadStatus::Contacted)->create(['position' => 3]);
        $b = Lead::factory()->status(LeadStatus::Contacted)->create(['position' => 1]);
        Lead::factory()->status(LeadStatus::Won)->create(['position' => 0]);

        expect(app(LeadBoardRepository::class)->idsInColumn(LeadStatus::Contacted))->toBe([$b->id, $a->id]);
    });
});
