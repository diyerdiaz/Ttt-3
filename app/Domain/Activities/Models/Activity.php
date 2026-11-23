<?php

declare(strict_types=1);

namespace App\Domain\Activities\Models;

use App\Domain\Activities\Enums\ActivityType;
use App\Domain\Leads\Models\Lead;
use Carbon\CarbonImmutable;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $lead_id
 * @property ActivityType|null $type
 * @property string $description
 * @property CarbonImmutable $occurred_at
 */
#[Fillable(['type', 'description', 'occurred_at'])]
#[UseFactory(ActivityFactory::class)]
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['type' => ActivityType::class, 'occurred_at' => 'immutable_datetime', 'lead_id' => 'integer'];
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
