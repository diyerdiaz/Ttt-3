<?php

declare(strict_types=1);

use App\Domain\Leads\Enums\LeadStatus;
use Illuminate\Support\Facades\Blade;

it('renders a labelled badge with a distinct colour for every stage', function (): void {
    $dots = [];

    foreach (LeadStatus::boardOrder() as $status) {
        $html = Blade::render('<x-stage-badge :status="$status" />', ['status' => $status]);

        expect($html)->toContain($status->label())->toContain('data-stage="'.$status->value.'"');
        preg_match('/size-1\.5 rounded-full (bg-\S+)"/', $html, $match);
        $dots[] = $match[1];
    }

    expect(array_unique($dots))->toHaveCount(count(LeadStatus::boardOrder()));
});

it('renders only a decorative dot, without text, in dot-only mode', function (): void {
    $html = Blade::render('<x-stage-badge :status="$status" dot-only />', ['status' => LeadStatus::Won]);

    expect($html)->toContain('aria-hidden="true"')->toContain('data-stage="won"')->not->toContain('Ganado');
});
