<?php

declare(strict_types=1);

use App\Domain\Activities\Enums\ActivityType;
use App\Domain\Activities\ValueObjects\ActivityData;
use Carbon\CarbonImmutable;

it('accepts an activity without type', function (): void {
    $data = new ActivityData('Sent the brochure', CarbonImmutable::now());

    expect($data->type)->toBeNull()->and($data->description)->toBe('Sent the brochure');
});

it('keeps the type and the moment it happened', function (): void {
    $moment = CarbonImmutable::parse('2026-11-20 10:30:00');
    $data = new ActivityData('Demo', $moment, ActivityType::Meeting);

    expect($data->type)->toBe(ActivityType::Meeting)->and($data->occurredAt)->toBe($moment);
});

it('requires a non-blank description', function (string $description): void {
    new ActivityData($description, CarbonImmutable::now());
})->with([
    'empty' => [''],
    'spaces' => ['   '],
    'tab and newlines' => ["\t\n\r\n"],
    'no-break space' => ["\u{00A0}"],
    'ideographic and line separators' => ["\u{3000}\u{2028}"],
])->throws(InvalidArgumentException::class, 'description');

it('accepts descriptions up to 5000 characters, counting characters and not bytes', function (): void {
    expect((new ActivityData(str_repeat('ñ', 5000), CarbonImmutable::now()))->description)->toHaveLength(5000);
});

it('rejects a description longer than 5000 characters', function (): void {
    new ActivityData(str_repeat('a', 5001), CarbonImmutable::now());
})->throws(InvalidArgumentException::class, 'description');

it('rejects a description holding a NUL byte, which PostgreSQL cannot store', function (): void {
    new ActivityData("bad\0text", CarbonImmutable::now());
})->throws(InvalidArgumentException::class, 'description');

it('rejects an unknown activity type', function (): void {
    ActivityType::from('fax');
})->throws(ValueError::class);

it('has a label for every activity type', function (ActivityType $type, string $label): void {
    expect($type->label())->toBe($label);
})->with([
    [ActivityType::Call, 'Llamada'],
    [ActivityType::Email, 'Correo'],
    [ActivityType::Meeting, 'Reunión'],
    [ActivityType::Note, 'Nota'],
]);

it('matches the values the database allows', function (): void {
    expect(array_map(static fn (ActivityType $t): string => $t->value, ActivityType::cases()))
        ->toBe(['call', 'email', 'meeting', 'note']);
});
