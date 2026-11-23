<?php

declare(strict_types=1);

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\Exceptions\InvalidStatusTransition;

it('describes the forbidden transition and exposes both ends', function (): void {
    $exception = InvalidStatusTransition::between(LeadStatus::Won, LeadStatus::New);

    expect($exception)->toBeInstanceOf(DomainException::class)
        ->and($exception->from)->toBe(LeadStatus::Won)
        ->and($exception->to)->toBe(LeadStatus::New)
        ->and($exception->getMessage())->toContain('Ganado')->toContain('Nuevo');
});
