<?php

declare(strict_types=1);

use App\Application\Leads\Actions\ExportLeads;
use App\Domain\Leads\Contracts\LeadExporter;
use App\Domain\Leads\Contracts\LeadExportSource;
use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\ValueObjects\LeadExportFilter;

it('hands the leads of the source to the chosen exporter without materializing them', function (): void {
    $source = new class implements LeadExportSource
    {
        public ?LeadExportFilter $received = null;

        public function stream(LeadExportFilter $filter): iterable
        {
            $this->received = $filter;

            yield (new Lead)->forceFill(['id' => 1]);
            yield (new Lead)->forceFill(['id' => 2]);
        }
    };
    $exporter = new class implements LeadExporter
    {
        public mixed $stream = null;

        /** @var list<int> */
        public array $ids = [];

        public function contentType(): string
        {
            return 'text/plain';
        }

        public function extension(): string
        {
            return 'txt';
        }

        public function write(iterable $leads, $out): void
        {
            $this->stream = $out;

            foreach ($leads as $lead) {
                $this->ids[] = (int) $lead->id;
            }
        }
    };
    $out = fopen('php://memory', 'w+b');
    $filter = new LeadExportFilter([LeadStatus::Won]);

    (new ExportLeads($source))($filter, $exporter, $out);

    expect($source->received)->toBe($filter)
        ->and($exporter->ids)->toBe([1, 2])
        ->and($exporter->stream)->toBe($out);
});

it('treats an empty status list as every status', function (): void {
    expect((new LeadExportFilter([]))->statuses)->toBe([])
        ->and((new LeadExportFilter([]))->isUnfiltered())->toBeTrue()
        ->and((new LeadExportFilter([LeadStatus::New]))->isUnfiltered())->toBeFalse();
});
