<?php

declare(strict_types=1);

namespace App\Domain\Leads\Policies;

use App\Domain\Identity\Abilities;
use App\Domain\Identity\Models\User;
use App\Domain\Leads\Models\Lead;
use Illuminate\Contracts\Auth\Access\Gate;

/**
 * Today only administrators manage leads, so every ability delegates to the admin ability.
 * Finer rules (ownership, roles) change here without touching the components.
 */
final readonly class LeadPolicy
{
    public function __construct(private Gate $gate) {}

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->isAdmin($user);
    }

    public function export(User $user): bool
    {
        return $this->isAdmin($user);
    }

    private function isAdmin(User $user): bool
    {
        return $this->gate->forUser($user)->allows(Abilities::ACCESS_ADMIN);
    }
}
