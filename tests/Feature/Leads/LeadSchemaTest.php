<?php

declare(strict_types=1);

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\LeadRows;

it('rejects an unknown status at the database level', function (): void {
    expect(fn () => LeadRows::insert(['status' => 'archived']))->toThrow(QueryException::class, 'leads_status_check');
});

it('rejects a blank name at the database level', function (string $name): void {
    expect(fn () => LeadRows::insert(['name' => $name]))->toThrow(QueryException::class, 'leads_name_not_blank_check');
})->with(['empty' => [''], 'spaces' => ['   ']]);

it('rejects a negative position at the database level', function (): void {
    expect(fn () => LeadRows::insert(['position' => -1]))->toThrow(QueryException::class, 'leads_position_check');
});

it('accepts every status declared by the enum', function (): void {
    foreach (LeadStatus::cases() as $i => $status) {
        LeadRows::insert(['status' => $status->value, 'position' => $i]);
    }

    expect(DB::table('leads')->count())->toBe(count(LeadStatus::cases()));
});

it('defaults the status to new', function (): void {
    DB::table('leads')->insert(['name' => 'Grace', 'position' => 0]);

    expect(DB::table('leads')->value('status'))->toBe('new');
});

it('rejects a duplicate position in the same status at commit', function (): void {
    expect(function (): void {
        DB::transaction(function (): void {
            LeadRows::insert(['position' => 0]);
            LeadRows::insert(['position' => 0]);
            // RefreshDatabase wraps each test in a transaction that never commits: force the deferred check.
            DB::statement('SET CONSTRAINTS leads_status_position_unique IMMEDIATE');
        });
    })->toThrow(QueryException::class, 'leads_status_position_unique');
});

it('allows the same position in different statuses', function (): void {
    LeadRows::insert(['status' => 'new', 'position' => 0]);
    LeadRows::insert(['status' => 'won', 'position' => 0]);

    expect(DB::table('leads')->count())->toBe(2);
});

it('defers the unique check so positions can be swapped within a transaction', function (): void {
    LeadRows::insert(['name' => 'A', 'position' => 0]);
    LeadRows::insert(['name' => 'B', 'position' => 1]);

    DB::transaction(function (): void {
        DB::table('leads')->where('name', 'A')->update(['position' => 1]);
        DB::table('leads')->where('name', 'B')->update(['position' => 0]);
    });

    expect(DB::table('leads')->orderBy('position')->pluck('name')->all())->toBe(['B', 'A']);
});

it('declares the unique constraint as deferrable initially deferred', function (): void {
    $row = DB::selectOne(
        "select condeferrable, condeferred from pg_constraint where conname = 'leads_status_position_unique'",
    );

    expect($row->condeferrable)->toBeTrue()->and($row->condeferred)->toBeTrue();
});

it('only carries the indexes its queries can use', function (): void {
    $indexes = DB::table('pg_indexes')->where('tablename', 'leads')->orderBy('indexname')->pluck('indexname')->all();

    expect($indexes)->toBe(['leads_pkey', 'leads_status_position_unique']);
});

it('uses an identity primary key and the documented column types', function (): void {
    $columns = collect(DB::select(
        "select column_name, data_type, character_maximum_length as len, is_nullable, is_identity
         from information_schema.columns where table_name = 'leads'",
    ))->keyBy('column_name');

    expect($columns['id']->is_identity)->toBe('YES')
        ->and($columns['name']->len)->toBe(150)
        ->and($columns['name']->is_nullable)->toBe('NO')
        ->and($columns['email']->len)->toBe(254)
        ->and($columns['phone']->len)->toBe(32)
        ->and($columns['company']->len)->toBe(150)
        ->and($columns['notes']->data_type)->toBe('text')
        ->and($columns['status']->len)->toBe(20)
        ->and($columns['position']->data_type)->toBe('integer')
        ->and($columns['created_at']->data_type)->toBe('timestamp with time zone')
        ->and($columns['updated_at']->data_type)->toBe('timestamp with time zone')
        ->and(Schema::hasColumns('leads', ['id', 'name', 'email', 'phone', 'company', 'notes', 'status', 'position']))->toBeTrue();
});

it('casts status to the LeadStatus enum on the model', function (): void {
    $lead = Lead::factory()->create(['status' => LeadStatus::Won]);

    expect($lead->fresh()->status)->toBe(LeadStatus::Won);
});

it('builds distinct positions with the factory', function (): void {
    Lead::factory()->count(3)->create();

    expect(Lead::query()->distinct()->count('position'))->toBe(3);
});
