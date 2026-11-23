<?php

declare(strict_types=1);

namespace App\Infrastructure\Export;

use App\Domain\Leads\Contracts\LeadExporter;
use App\Domain\Leads\Models\Lead;

final class CsvLeadExporter implements LeadExporter
{
    private const string BOM = "\xEF\xBB\xBF";

    private const array HEADER = ['ID', 'Nombre', 'Correo', 'Teléfono', 'Empresa', 'Estado', 'Notas', 'Creado'];

    public function contentType(): string
    {
        return 'text/csv; charset=utf-8';
    }

    public function extension(): string
    {
        return 'csv';
    }

    public function write(iterable $leads, $out): void
    {
        fwrite($out, self::BOM);
        $this->putRow($out, self::HEADER);

        foreach ($leads as $lead) {
            $this->putRow($out, $this->row($lead));
        }
    }

    /**
     * @return list<string>
     */
    private function row(Lead $lead): array
    {
        return [
            (string) $lead->id,
            CsvCellSanitizer::sanitize($lead->name),
            CsvCellSanitizer::sanitize($lead->email),
            CsvCellSanitizer::sanitize($lead->phone),
            CsvCellSanitizer::sanitize($lead->company),
            CsvCellSanitizer::sanitize($lead->status->label()),
            CsvCellSanitizer::sanitize($lead->notes),
            $lead->created_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    /**
     * @param  resource  $out
     * @param  list<string>  $cells
     */
    private function putRow($out, array $cells): void
    {
        fputcsv($out, $cells, escape: '', eol: "\r\n");
    }
}
