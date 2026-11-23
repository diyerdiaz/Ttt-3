<?php

declare(strict_types=1);

namespace App\Domain\Leads\Models;

use App\Domain\Activities\Models\Activity;
use App\Domain\Leads\Enums\LeadStatus;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $company
 * @property string|null $notes
 * @property LeadStatus $status
 * @property int $position
 */
#[Fillable(['name', 'email', 'phone', 'company', 'notes'])]
#[UseFactory(LeadFactory::class)]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => 'new'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => LeadStatus::class, 'position' => 'integer'];
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}
