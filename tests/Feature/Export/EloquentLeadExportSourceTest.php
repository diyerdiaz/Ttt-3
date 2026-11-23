<?php

declare(strict_types=1);

use App\Domain\Leads\Contracts\LeadExportSource;
use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\ValueObjects\LeadExportFilter;
use Tests\Support\ExportProbe;
use Tests\Support\LeadRows;
use Tests\Support\QueryCounter;

it('streams leads in id order using lazy chunks', function (): void {
    LeadRows::insertMany(1001);

    $ids = [];
    $queries = QueryCounter::during(function () use (&$ids): void {
        $ids = ExportProbe::ids(new LeadExportFilter([]));
    });
    $sorted = $ids;
    sort($sorted);

    expect($ids)->toHaveCount(1001)->and($ids)->toBe($sorted)->and($queries)->toBe(3);
});

it('reads each chunk with a keyset condition on the primary key', function (): void {
    LeadRows::insertMany(600);
    $sql = [];

    DB::listen(static function ($query) use (&$sql): void {
        $sql[] = $query->sql;
    });
    ExportProbe::ids(new LeadExportFilter([]));

    expect($sql)->toHaveCount(2)
        ->and($sql[0])->toContain('order by "id" asc')->toContain('limit 500')
        ->and($sql[1])->toContain('"id" > ?');
});

it('filters exported leads by one or several statuses', function (): void {
    $new = Lead::factory()->create();
    $won = Lead::factory()->status(LeadStatus::Won)->create();
    $lost = Lead::factory()->status(LeadStatus::Lost)->create();

    expect(ExportProbe::ids(new LeadExportFilter([LeadStatus::Won])))->toBe([$won->id])
        ->and(ExportProbe::ids(new LeadExportFilter([LeadStatus::Won, LeadStatus::New])))->toBe([$new->id, $won->id])
        ->and(ExportProbe::ids(new LeadExportFilter([])))->toBe([$new->id, $won->id, $lost->id]);
});

it('selects only the exported columns', function (): void {
    Lead::factory()->create();

    $lead = iterator_to_array(app(LeadExportSource::class)->stream(new LeadExportFilter([])), false)[0];

    expect(array_keys($lead->getAttributes()))->toBe(['id', 'name', 'email', 'phone', 'company', 'status', 'notes', 'created_at']);
});

it('runs a constant number of queries per chunk and no per-lead queries', function (): void {
    LeadRows::insertMany(5);
    $few = QueryCounter::during(fn () => ExportProbe::ids(new LeadExportFilter([])));
    LeadRows::insertMany(45, firstPosition: 100);
    $many = QueryCounter::during(fn () => ExportProbe::ids(new LeadExportFilter([])));

    expect($few)->toBe(1)->and($many)->toBe(1);
});
