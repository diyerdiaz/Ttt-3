<?php

declare(strict_types=1);

namespace App\Domain\Activities\Policies;

use App\Domain\Identity\Abilities;
use App\Domain\Identity\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;

/**
 * Today only administrators work with activities, so every ability delegates to the admin ability.
 */
final readonly class ActivityPolicy
{
    public function __construct(private Gate $gate) {}

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    private function isAdmin(User $user): bool
    {
        return $this->gate->forUser($user)->allows(Abilities::ACCESS_ADMIN);
    }
}
