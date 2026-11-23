<?php

declare(strict_types=1);

use App\Domain\Leads\Contracts\LeadBoardRepository;
use App\Domain\Leads\Contracts\LeadRepository;
use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\ValueObjects\LeadData;
use App\Domain\Leads\ValueObjects\PhoneNumber;
use App\Domain\Shared\Contracts\TransactionManager;
use App\Domain\Shared\ValueObjects\EmailAddress;
use App\Infrastructure\Persistence\DatabaseTransactionManager;
use App\Infrastructure\Persistence\EloquentLeadRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

it('binds the lead contracts to the Eloquent implementation', function (): void {
    expect(app(LeadRepository::class))->toBeInstanceOf(EloquentLeadRepository::class)
        ->and(app(LeadBoardRepository::class))->toBeInstanceOf(EloquentLeadRepository::class)
        ->and(app(TransactionManager::class))->toBeInstanceOf(DatabaseTransactionManager::class);
});

it('creates a lead from the data at the given position', function (): void {
    $lead = app(LeadRepository::class)->create(new LeadData(
        'Ada',
        EmailAddress::fromString('ADA@example.com'),
        PhoneNumber::fromString('+57 300 123 4567'),
        'Analytical Engines',
        'Notes',
        LeadStatus::Proposal,
    ), 3);

    expect($lead->exists)->toBeTrue()
        ->and($lead->fresh()?->only(['name', 'email', 'phone', 'company', 'notes', 'position']))->toBe([
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'phone' => '+57 300 123 4567',
            'company' => 'Analytical Engines',
            'notes' => 'Notes',
            'position' => 3,
        ])
        ->and($lead->fresh()?->status)->toBe(LeadStatus::Proposal);
});

it('finds leads by id', function (): void {
    $lead = Lead::factory()->create();
    $repository = app(LeadRepository::class);

    expect($repository->find((int) $lead->id)?->is($lead))->toBeTrue()
        ->and($repository->find(0))->toBeNull()
        ->and($repository->findOrFail((int) $lead->id)->is($lead))->toBeTrue();
});

it('fails to find a missing lead', function (): void {
    app(LeadRepository::class)->findOrFail(0);
})->throws(ModelNotFoundException::class);

it('returns the next free position of a column', function (): void {
    $repository = app(LeadRepository::class);
    Lead::factory()->status(LeadStatus::Won)->create(['position' => 0]);
    Lead::factory()->status(LeadStatus::Won)->create(['position' => 4]);

    expect($repository->nextPositionIn(LeadStatus::Won))->toBe(5)
        ->and($repository->nextPositionIn(LeadStatus::Lost))->toBe(0);
});

it('updates a lead keeping or replacing its position', function (): void {
    $lead = Lead::factory()->status(LeadStatus::New)->create(['position' => 7]);
    $repository = app(LeadRepository::class);

    $kept = $repository->update($lead, new LeadData('Renamed', status: LeadStatus::New));
    expect($kept->fresh()?->name)->toBe('Renamed')
        ->and($kept->fresh()?->position)->toBe(7)
        ->and($kept->fresh()?->email)->toBeNull();

    $moved = $repository->update($lead, new LeadData('Renamed', status: LeadStatus::Won), 2);
    expect($moved->fresh()?->status)->toBe(LeadStatus::Won)
        ->and($moved->fresh()?->position)->toBe(2);
});

describe('search', function (): void {
    it('lists everything newest first when the term is blank', function (?string $term): void {
        $old = Lead::factory()->create(['name' => 'Old']);
        $new = Lead::factory()->create(['name' => 'New']);

        $page = app(LeadRepository::class)->search($term, 10);

        expect($page->items())->toHaveCount(2)
            ->and($page->items()[0]->is($new))->toBeTrue()
            ->and($page->items()[1]->is($old))->toBeTrue();
    })->with([null, '', '   ']);

    it('matches name, email or company case-insensitively', function (string $term, string $expected): void {
        Lead::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@engines.test', 'company' => 'Analytical']);
        Lead::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@navy.test', 'company' => 'Cobol Inc']);

        $names = array_map(
            static fn (Lead $lead): string => $lead->name,
            app(LeadRepository::class)->search($term, 10)->items(),
        );

        expect($names)->toBe([$expected]);
    })->with([
        'name' => ['LOVELACE', 'Ada Lovelace'],
        'email' => ['Grace@NAVY', 'Grace Hopper'],
        'company' => ['cobol', 'Grace Hopper'],
        'trimmed' => ['  hopper ', 'Grace Hopper'],
    ]);

    it('treats like wildcards in the term literally', function (string $term): void {
        Lead::factory()->create(['name' => 'Ada', 'email' => 'ada@x.test', 'company' => 'Acme']);
        Lead::factory()->create(['name' => '100% Real', 'email' => 'real@x.test', 'company' => 'a_b']);

        $found = app(LeadRepository::class)->search($term, 10)->items();

        expect($found)->toHaveCount(1);
    })->with(['percent' => ['%'], 'underscore' => ['_']]);

    it('does not fail on a backslash in the term', function (): void {
        Lead::factory()->create();

        expect(app(LeadRepository::class)->search('\\', 10)->items())->toBe([]);
    });

    it('paginates and loads only the listing columns', function (): void {
        Lead::factory()->count(3)->create();

        $page = app(LeadRepository::class)->search(null, 2);

        expect($page->total())->toBe(3)
            ->and($page->items())->toHaveCount(2)
            ->and(array_keys($page->items()[0]->getAttributes()))->not->toContain('notes');
    });
});

it('takes the board advisory lock that other connections can see', function (): void {
    config(['database.connections.second' => config('database.connections.'.config('database.default'))]);
    $other = DB::connection('second');
    $probe = static fn (): bool => (bool) $other->selectOne('select pg_try_advisory_xact_lock(?, ?) as free', [...EloquentLeadRepository::lockKey()])->free;

    expect($probe())->toBeTrue();

    app(LeadBoardRepository::class)->lockBoard();

    expect($probe())->toBeFalse();

    DB::purge('second');
});

it('uses the namespaced two-integer advisory lock form', function (): void {
    [$namespace, $key] = EloquentLeadRepository::lockKey();

    expect($namespace)->toBeInt()->and($key)->toBeInt();
});

it('refuses to take the board lock outside a transaction', function (): void {
    DB::rollBack();

    expect(DB::transactionLevel())->toBe(0);

    app(LeadBoardRepository::class)->lockBoard();
})->throws(LogicException::class, 'transaction');

it('keeps the stored status when the data leaves it unspecified', function (): void {
    $lead = Lead::factory()->status(LeadStatus::Qualified)->create(['position' => 4]);

    app(LeadRepository::class)->update($lead, new LeadData('Renamed'));

    expect($lead->fresh()?->status)->toBe(LeadStatus::Qualified)->and($lead->fresh()?->position)->toBe(4);
});

it('creates a lead in the status of the data', function (): void {
    $lead = app(LeadRepository::class)->create(new LeadData('Ada', status: LeadStatus::Lost), 0);

    expect($lead->fresh()?->status)->toBe(LeadStatus::Lost);
});

it('does not list the columns the board and the list do not render', function (): void {
    Lead::factory()->create();

    $attributes = array_keys(app(LeadRepository::class)->search(null, 10)->items()[0]->getAttributes());

    expect($attributes)->not->toContain('position')->not->toContain('created_at')->not->toContain('notes');
});
