<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Leads\Contracts\LeadRepository;
use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Exceptions\InvalidStatusTransition;
use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\ValueObjects\LeadData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Decorator that refuses every status change, to exercise the handling of InvalidStatusTransition
 * while the real rule still allows every transition.
 */
final readonly class RefusingTransitionRepository implements LeadRepository
{
    public function __construct(private LeadRepository $inner) {}

    public function find(int $id): ?Lead
    {
        return $this->inner->find($id);
    }

    public function findOrFail(int $id): Lead
    {
        return $this->inner->findOrFail($id);
    }

    public function create(LeadData $data, int $position): Lead
    {
        return $this->inner->create($data, $position);
    }

    public function update(Lead $lead, LeadData $data, ?int $position = null): Lead
    {
        throw InvalidStatusTransition::between($lead->status, $data->status ?? LeadStatus::New);
    }

    public function nextPositionIn(LeadStatus $status): int
    {
        return $this->inner->nextPositionIn($status);
    }

    public function search(?string $term, int $perPage): LengthAwarePaginator
    {
        return $this->inner->search($term, $perPage);
    }
}
