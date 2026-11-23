<?php

declare(strict_types=1);

use App\Domain\Leads\Enums\LeadStatus;
use App\Domain\Leads\ValueObjects\LeadData;

it('keeps the status unspecified unless one is given', function (): void {
    expect((new LeadData('Ada'))->status)->toBeNull()
        ->and((new LeadData('Ada', status: LeadStatus::Won))->status)->toBe(LeadStatus::Won);
});

it('returns a copy with the given status and leaves the original untouched', function (): void {
    $original = new LeadData('Ada', company: 'Analytical');
    $copy = $original->withStatus(LeadStatus::Lost);

    expect($copy->status)->toBe(LeadStatus::Lost)
        ->and($copy->name)->toBe('Ada')
        ->and($copy->company)->toBe('Analytical')
        ->and($original->status)->toBeNull();
});

it('accepts names up to 150 characters, counting characters and not bytes', function (): void {
    expect((new LeadData(str_repeat('a', 150)))->name)->toHaveLength(150)
        ->and(strlen((new LeadData(str_repeat('ñ', 150)))->name))->toBe(300);
});

it('rejects an empty or blank name', function (string $name): void {
    new LeadData($name);
})->with(['empty' => [''], 'spaces' => ['   '], 'tab and newline' => ["\t\n"]])
    ->throws(InvalidArgumentException::class, 'name');

it('rejects a name longer than 150 characters', function (): void {
    new LeadData(str_repeat('ñ', 151));
})->throws(InvalidArgumentException::class, 'name');
