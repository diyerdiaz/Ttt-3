<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Models\Lead;

/**
 * Fixed board layouts for the move tests.
 */
final class Board
{
    /**
     * Ids 1-3 in New (positions 0-2) and 4-5 in Qualified (positions 0-1).
     */
    public static function inMemory(): InMemoryLeadStore
    {
        $store = new InMemoryLeadStore;
        foreach (self::layout() as [$status, $position]) {
            $store->seed($status, $position);
        }
        $store->calls = [];

        return $store;
    }

    /**
     * Same layout persisted, keyed by 1-based creation order.
     *
     * @return array<int, Lead>
     */
    public static function persisted(): array
    {
        $leads = [];
        foreach (self::layout() as $i => [$status, $position]) {
            $leads[$i + 1] = Lead::factory()->status($status)->create(['position' => $position]);
        }

        return $leads;
    }

    /**
     * @param  list<Lead>  $leads
     * @return list<int>
     */
    public static function ids(array $leads): array
    {
        return array_map(static fn (Lead $lead): int => (int) $lead->id, $leads);
    }

    /**
     * @return list<array{LeadStatus, int}>
     */
    private static function layout(): array
    {
        return [[LeadStatus::New, 0], [LeadStatus::New, 1], [LeadStatus::New, 2], [LeadStatus::Qualified, 0], [LeadStatus::Qualified, 1]];
    }
}
