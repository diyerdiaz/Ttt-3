<?php

declare(strict_types=1);

use App\Domain\Leads\Models\Lead;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActivityRows;

it('rejects an unknown type at the database level', function (): void {
    $lead = Lead::factory()->create();

    expect(fn () => ActivityRows::insert($lead->id, ['type' => 'fax']))->toThrow(QueryException::class, 'activities_type_check');
});

it('accepts a null type and every declared one', function (): void {
    $lead = Lead::factory()->create();

    foreach ([null, 'call', 'email', 'meeting', 'note'] as $type) {
        ActivityRows::insert($lead->id, ['type' => $type]);
    }

    expect(DB::table('activities')->count())->toBe(5);
});

it('rejects a blank description at the database level', function (string $description): void {
    $lead = Lead::factory()->create();

    expect(fn () => ActivityRows::insert($lead->id, ['description' => $description]))
        ->toThrow(QueryException::class, 'activities_description_not_blank_check');
})->with(['empty' => [''], 'spaces' => ['   ']]);

it('rejects an activity without a moment', function (): void {
    $lead = Lead::factory()->create();

    expect(fn () => ActivityRows::insert($lead->id, ['occurred_at' => null]))->toThrow(QueryException::class, 'occurred_at');
});

it('rejects an activity for a lead that does not exist', function (): void {
    expect(fn () => ActivityRows::insert(999_999))->toThrow(QueryException::class, 'activities_lead_id_foreign');
});

it('deletes activities when their lead is deleted', function (): void {
    $lead = Lead::factory()->create();
    $other = Lead::factory()->create();
    ActivityRows::insert($lead->id);
    ActivityRows::insert($lead->id);
    ActivityRows::insert($other->id);

    DB::table('leads')->where('id', $lead->id)->delete();

    expect(DB::table('activities')->pluck('lead_id')->all())->toBe([$other->id]);
});

it('indexes the timeline by lead, newest moment and id', function (): void {
    $indexes = DB::table('pg_indexes')->where('tablename', 'activities')->orderBy('indexname')->pluck('indexdef', 'indexname')->all();

    expect(array_keys($indexes))->toBe(['activities_lead_id_occurred_at_id_index', 'activities_pkey'])
        ->and($indexes['activities_lead_id_occurred_at_id_index'])->toContain('(lead_id, occurred_at DESC, id DESC)');
});

it('uses an identity key, timestamptz columns and the documented types', function (): void {
    $columns = collect(DB::select(
        "select column_name, data_type, is_nullable, is_identity from information_schema.columns where table_name = 'activities'",
    ))->keyBy('column_name');

    expect($columns['id']->is_identity)->toBe('YES')
        ->and($columns['lead_id']->is_nullable)->toBe('NO')
        ->and($columns['type']->is_nullable)->toBe('YES')
        ->and($columns['description']->data_type)->toBe('text')
        ->and($columns['description']->is_nullable)->toBe('NO')
        ->and($columns['occurred_at']->data_type)->toBe('timestamp with time zone')
        ->and($columns['occurred_at']->is_nullable)->toBe('NO')
        ->and($columns['created_at']->data_type)->toBe('timestamp with time zone')
        ->and($columns['updated_at']->data_type)->toBe('timestamp with time zone');
});
