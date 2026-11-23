<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Leads\Contracts\LeadExportSource;
use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\ValueObjects\LeadExportFilter;
use Illuminate\Database\Eloquent\Builder;

final class EloquentLeadExportSource implements LeadExportSource
{
    private const int CHUNK_SIZE = 500;

    private const array COLUMNS = ['id', 'name', 'email', 'phone', 'company', 'status', 'notes', 'created_at'];

    public function stream(LeadExportFilter $filter): iterable
    {
        return Lead::query()
            ->select(self::COLUMNS)
            ->when(! $filter->isUnfiltered(), static function (Builder $query) use ($filter): void {
                $query->whereIn('status', array_map(static fn (LeadStatus $status): string => $status->value, $filter->statuses));
            })
            ->lazyById(self::CHUNK_SIZE);
    }
}
