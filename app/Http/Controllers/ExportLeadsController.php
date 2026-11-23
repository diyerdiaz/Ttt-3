<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Leads\Actions\ExportLeads;
use App\Domain\Leads\Contracts\LeadExporterFactory;
use App\Domain\Shared\ValueObjects\DisplayTimezone;
use App\Http\Requests\ExportLeadsRequest;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportLeadsController extends Controller
{
    private const string FORMAT = 'csv';

    public function __invoke(ExportLeadsRequest $request, ExportLeads $export, LeadExporterFactory $exporters): StreamedResponse
    {
        $exporter = $exporters->for(self::FORMAT);
        $filter = $request->toFilter();

        return response()->streamDownload(
            static function () use ($export, $filter, $exporter): void {
                $out = fopen('php://output', 'wb');
                if ($out === false) {
                    throw new RuntimeException('Cannot open the response output stream.');
                }

                try {
                    $export($filter, $exporter, $out);
                } finally {
                    fclose($out);
                }
            },
            'leads-'.DisplayTimezone::now()->format('Ymd-His').'.'.$exporter->extension(),
            [
                'Content-Type' => $exporter->contentType(),
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'no-store, private',
            ],
        );
    }
}
