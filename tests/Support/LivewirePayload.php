<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Http\Livewire\Leads\LeadIndex;
use Livewire\Livewire;

/**
 * Builds the body the Livewire JavaScript posts to the update endpoint, from a real component snapshot.
 */
final class LivewirePayload
{
    /**
     * @return array{components: list<array{snapshot: string, updates: array<string, mixed>, calls: list<mixed>}>}
     */
    public static function leadIndex(): array
    {
        $snapshot = Livewire::test(LeadIndex::class)->snapshot;

        return ['components' => [['snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR), 'updates' => [], 'calls' => []]]];
    }
}
