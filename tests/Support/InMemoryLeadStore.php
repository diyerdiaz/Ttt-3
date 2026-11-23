<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Leads\Contracts\LeadBoardRepository;
use App\Domain\Leads\Contracts\LeadRepository;
use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\ValueObjects\LeadData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

/**
 * Fake of the lead contracts for unit tests of Actions. `$calls` records the order of side effects.
 */
final class InMemoryLeadStore implements LeadBoardRepository, LeadRepository
{
    /** @var array<int, Lead> */
    public array $leads = [];

    /** @var list<string> */
    public array $calls = [];

    private int $lastId = 0;

    public function seed(LeadStatus $status, int $position): Lead
    {
        $lead = new Lead(['name' => 'Seed '.($this->lastId + 1)]);
        $lead->status = $status;
        $lead->position = $position;
        $lead->setAttribute('id', ++$this->lastId);

        return $this->leads[$this->lastId] = $lead;
    }

    public function lockBoard(): void
    {
        $this->calls[] = 'lock';
    }

    public function columns(): array
    {
        $columns = [];

        foreach (LeadStatus::boardOrder() as $status) {
            $inColumn = array_values(array_filter($this->leads, static fn (Lead $lead): bool => $lead->status === $status));
            usort($inColumn, static fn (Lead $a, Lead $b): int => $a->position <=> $b->position);
            $columns[$status->value] = $inColumn;
        }

        return $columns;
    }

    public function findForUpdate(int $id): Lead
    {
        $this->calls[] = "find-for-update:{$id}";

        return $this->findOrFail($id);
    }

    public function idsInColumn(LeadStatus $status): array
    {
        return array_map(static fn (Lead $lead): int => (int) $lead->id, $this->columns()[$status->value]);
    }

    public function placeColumn(LeadStatus $status, array $orderedIds): void
    {
        $this->calls[] = "place:{$status->value}:".implode(',', $orderedIds);

        foreach (array_values($orderedIds) as $index => $id) {
            $this->leads[$id]->status = $status;
            $this->leads[$id]->position = $index;
        }
    }

    public function find(int $id): ?Lead
    {
        return $this->leads[$id] ?? null;
    }

    public function findOrFail(int $id): Lead
    {
        return $this->find($id) ?? throw (new ModelNotFoundException)->setModel(Lead::class, [$id]);
    }

    public function create(LeadData $data, int $position): Lead
    {
        $status = $data->status ?? LeadStatus::New;
        $this->calls[] = "create:{$status->value}@{$position}";

        $lead = $this->seed($status, $position);
        $lead->fill($this->attributes($data));
        $lead->syncOriginal();

        return $lead;
    }

    public function update(Lead $lead, LeadData $data, ?int $position = null): Lead
    {
        $this->calls[] = 'update:'.($position ?? 'keep');

        $lead->fill($this->attributes($data));

        if ($data->status !== null) {
            $lead->status = $data->status;
        }

        if ($position !== null) {
            $lead->position = $position;
        }

        $lead->syncChanges()->syncOriginal();

        return $lead;
    }

    public function nextPositionIn(LeadStatus $status): int
    {
        $this->calls[] = "next:{$status->value}";

        $positions = array_map(
            static fn (Lead $lead): int => $lead->position,
            array_filter($this->leads, static fn (Lead $lead): bool => $lead->status === $status),
        );

        return $positions === [] ? 0 : max($positions) + 1;
    }

    public function search(?string $term, int $perPage): LengthAwarePaginator
    {
        return new Paginator(array_values($this->leads), count($this->leads), $perPage);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(LeadData $data): array
    {
        return [
            'name' => $data->name,
            'email' => $data->email?->value(),
            'phone' => $data->phone?->value(),
            'company' => $data->company,
            'notes' => $data->notes,
        ];
    }
}
