<?php

declare(strict_types=1);

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use App\Infrastructure\Export\CsvLeadExporter;
use Tests\Support\CsvDownload;

/**
 * @param  array<string, mixed>  $attributes
 */
function csvLead(array $attributes = []): Lead
{
    return (new Lead)->forceFill(array_merge([
        'id' => 1,
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.test',
        'phone' => '+57 300 111 2222',
        'company' => 'Analytical',
        'notes' => 'Primera nota',
        'status' => LeadStatus::Won,
        'created_at' => '2026-11-20 10:30:00',
    ], $attributes));
}

/**
 * @param  iterable<Lead>  $leads
 */
function exportToString(iterable $leads): string
{
    $out = fopen('php://memory', 'w+b');
    assert($out !== false);
    (new CsvLeadExporter)->write($leads, $out);
    rewind($out);

    return (string) stream_get_contents($out);
}

it('describes itself as a csv file', function (): void {
    $exporter = new CsvLeadExporter;

    expect($exporter->contentType())->toBe('text/csv; charset=utf-8')
        ->and($exporter->extension())->toBe('csv');
});

it('writes a header row and one row per lead with fputcsv quoting', function (): void {
    $csv = exportToString([
        csvLead(),
        csvLead(['id' => 2, 'name' => 'Grace, "Amazing" Hopper', 'notes' => "line one\nline two", 'status' => LeadStatus::New]),
    ]);

    expect(str_starts_with($csv, CsvDownload::BOM))->toBeTrue()
        ->and(CsvDownload::rows($csv))->toBe([
            ['ID', 'Nombre', 'Correo', 'Teléfono', 'Empresa', 'Estado', 'Notas', 'Creado'],
            ['1', 'Ada Lovelace', 'ada@example.test', "'+57 300 111 2222", 'Analytical', 'Ganado', 'Primera nota', '2026-11-20 10:30:00'],
            ['2', 'Grace, "Amazing" Hopper', 'ada@example.test', "'+57 300 111 2222", 'Analytical', 'Nuevo', "line one\nline two", '2026-11-20 10:30:00'],
        ])
        ->and($csv)->toContain('"Grace, ""Amazing"" Hopper"');
});

it('writes only the header for an empty export', function (): void {
    expect(CsvDownload::rows(exportToString([])))->toHaveCount(1);
});

it('writes empty cells for missing optional values', function (): void {
    $rows = CsvDownload::rows(exportToString([csvLead(['email' => null, 'phone' => null, 'company' => null, 'notes' => null])]));

    expect($rows[1])->toBe(['1', 'Ada Lovelace', '', '', '', 'Ganado', '', '2026-11-20 10:30:00']);
});

it('neutralizes formula payloads in every text cell', function (): void {
    $payload = '=HYPERLINK("http://evil.test")';
    $rows = CsvDownload::rows(exportToString([csvLead([
        'name' => $payload, 'email' => '@evil', 'company' => '  +cmd', 'notes' => "\t-1",
    ])]));

    expect($rows[1][1])->toBe("'".$payload)
        ->and($rows[1][2])->toBe("'@evil")
        ->and($rows[1][4])->toBe("'  +cmd")
        ->and($rows[1][6])->toBe("'\t-1");
});

it('consumes a lazy iterable one lead at a time', function (): void {
    $produced = 0;
    $leads = (function () use (&$produced): Generator {
        foreach ([1, 2, 3] as $id) {
            $produced++;
            yield csvLead(['id' => $id]);
        }
    })();

    expect(CsvDownload::rows(exportToString($leads)))->toHaveCount(4)->and($produced)->toBe(3);
});
