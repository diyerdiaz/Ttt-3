<?php

declare(strict_types=1);

use App\Domain\Leads\Enums\LeadStatus;

it('exposes the board columns in business order', function (): void {
    expect(LeadStatus::boardOrder())->toBe([
        LeadStatus::New,
        LeadStatus::Contacted,
        LeadStatus::Qualified,
        LeadStatus::Proposal,
        LeadStatus::Won,
        LeadStatus::Lost,
    ]);
});

it('has a human readable label for every status', function (): void {
    expect(array_map(static fn (LeadStatus $s): string => $s->label(), LeadStatus::boardOrder()))
        ->toBe(['Nuevo', 'Contactado', 'Calificado', 'Propuesta', 'Ganado', 'Perdido']);
});

it('flags only won and lost as terminal', function (): void {
    $terminal = array_filter(LeadStatus::cases(), static fn (LeadStatus $s): bool => $s->isTerminal());

    expect(array_values($terminal))->toBe([LeadStatus::Won, LeadStatus::Lost]);
});

it('allows free transitions between open statuses', function (): void {
    expect(LeadStatus::New->canTransitionTo(LeadStatus::Proposal))->toBeTrue()
        ->and(LeadStatus::Proposal->canTransitionTo(LeadStatus::New))->toBeTrue();
});

it('allows moving between any two statuses', function (LeadStatus $from, LeadStatus $to): void {
    expect($from->canTransitionTo($to))->toBeTrue();
})->with(function (): Generator {
    foreach (LeadStatus::cases() as $from) {
        foreach (LeadStatus::cases() as $to) {
            yield "{$from->value} -> {$to->value}" => [$from, $to];
        }
    }
});

it('treats a same-status move as a valid reorder', function (): void {
    expect(LeadStatus::Qualified->canTransitionTo(LeadStatus::Qualified))->toBeTrue();
});
