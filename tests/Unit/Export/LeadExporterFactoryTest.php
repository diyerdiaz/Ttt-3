<?php

declare(strict_types=1);

use App\Domain\Leads\Contracts\LeadExporterFactory;
use App\Domain\Leads\Exceptions\UnsupportedExportFormat;
use App\Infrastructure\Export\ConfiguredLeadExporterFactory;
use App\Infrastructure\Export\CsvLeadExporter;

it('resolves the csv exporter strategy by format and fails on unknown format', function (): void {
    $factory = new ConfiguredLeadExporterFactory;

    expect($factory)->toBeInstanceOf(LeadExporterFactory::class)
        ->and($factory->for('csv'))->toBeInstanceOf(CsvLeadExporter::class)
        ->and(fn () => $factory->for('xlsx'))->toThrow(UnsupportedExportFormat::class, 'xlsx');
});

it('does not guess a format from case or whitespace', function (string $format): void {
    expect(fn () => (new ConfiguredLeadExporterFactory)->for($format))->toThrow(UnsupportedExportFormat::class);
})->with(['CSV', ' csv', '', 'csv ']);
