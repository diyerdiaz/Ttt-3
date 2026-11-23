<?php

declare(strict_types=1);

use App\Domain\Leads\Enums\LeadStatus;
use App\Infrastructure\Export\CsvLeadExporter;
use Tests\Support\CsvDownload;
use Tests\Support\LeadCsv;

it('describes itself as a csv file', function (): void {
    $exporter = new CsvLeadExporter;

    expect($exporter->contentType())->toBe('text/csv; charset=utf-8')
        ->and($exporter->extension())->toBe('csv');
});

it('writes a header row and one row per lead with fputcsv quoting', function (): void {
    $csv = LeadCsv::render([
        LeadCsv::lead(),
        LeadCsv::lead(['id' => 2, 'name' => 'Grace, "Amazing" Hopper', 'notes' => "line one\nline two", 'status' => LeadStatus::New]),
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
    expect(CsvDownload::rows(LeadCsv::render([])))->toHaveCount(1);
});

it('writes empty cells for missing optional values', function (): void {
    $rows = CsvDownload::rows(LeadCsv::render([LeadCsv::lead(['email' => null, 'phone' => null, 'company' => null, 'notes' => null])]));

    expect($rows[1])->toBe(['1', 'Ada Lovelace', '', '', '', 'Ganado', '', '2026-11-20 10:30:00']);
});

it('neutralizes formula payloads in every text cell', function (): void {
    $payload = '=HYPERLINK("http://evil.test")';
    $rows = CsvDownload::rows(LeadCsv::render([LeadCsv::lead([
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
            yield LeadCsv::lead(['id' => $id]);
        }
    })();

    expect(CsvDownload::rows(LeadCsv::render($leads)))->toHaveCount(4)->and($produced)->toBe(3);
});
