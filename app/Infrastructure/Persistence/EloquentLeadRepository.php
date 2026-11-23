<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Leads\Contracts\LeadBoardRepository;
use App\Domain\Leads\Contracts\LeadRepository;
use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;
use App\Domain\Leads\ValueObjects\LeadData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;

final class EloquentLeadRepository implements LeadBoardRepository, LeadRepository
{
    private const array LISTING_COLUMNS = ['id', 'name', 'email', 'phone', 'company', 'status'];

    private const int LOCK_NAMESPACE = 7301;

    private const int BOARD_LOCK = 1;

    /**
     * @return array{int, int} namespace and key of the advisory lock, so it cannot collide with other locks
     */
    public static function lockKey(): array
    {
        return [self::LOCK_NAMESPACE, self::BOARD_LOCK];
    }

    public function lockBoard(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('The board lock must be taken inside a transaction, or it is released at once.');
        }

        DB::select('select pg_advisory_xact_lock(?, ?)', self::lockKey());
    }

    public function find(int $id): ?Lead
    {
        return Lead::query()->find($id);
    }

    public function findOrFail(int $id): Lead
    {
        return Lead::query()->findOrFail($id);
    }

    public function create(LeadData $data, int $position): Lead
    {
        $lead = new Lead($this->attributes($data));
        $lead->status = $data->status ?? LeadStatus::New;
        $lead->position = $position;
        $lead->save();

        return $lead;
    }

    public function update(Lead $lead, LeadData $data, ?int $position = null): Lead
    {
        $lead->fill($this->attributes($data));

        if ($data->status !== null) {
            $lead->status = $data->status;
        }

        if ($position !== null) {
            $lead->position = $position;
        }

        $lead->save();

        return $lead;
    }

    public function nextPositionIn(LeadStatus $status): int
    {
        $max = Lead::query()->where('status', $status)->max('position');

        return is_numeric($max) ? (int) $max + 1 : 0;
    }

    public function search(?string $term, int $perPage): LengthAwarePaginator
    {
        $term = trim((string) $term);

        return Lead::query()
            ->select(self::LISTING_COLUMNS)
            ->when($term !== '', static function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '\\%_').'%';

                $query->where(static function (Builder $match) use ($like): void {
                    $match->whereLike('name', $like)->orWhereLike('email', $like)->orWhereLike('company', $like);
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
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
