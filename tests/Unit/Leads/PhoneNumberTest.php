<?php

declare(strict_types=1);

use App\Domain\Leads\ValueObjects\PhoneNumber;

it('validates and normalizes phone numbers', function (string $input, string $expected): void {
    expect(PhoneNumber::fromString($input)->value())->toBe($expected);
})->with([
    'trims' => ['  +57 300 123 4567  ', '+57 300 123 4567'],
    'collapses inner whitespace' => ["+57   300\t123", '+57 300 123'],
    'keeps allowed punctuation' => ['(601) 555-1234', '(601) 555-1234'],
    'minimum length' => ['123456', '123456'],
    'maximum length' => [str_repeat('1', 32), str_repeat('1', 32)],
]);

it('rejects invalid phone numbers', function (string $input): void {
    expect(fn () => PhoneNumber::fromString($input))->toThrow(InvalidArgumentException::class);
})->with([
    'empty' => [''],
    'blank' => ['   '],
    'too short' => ['12345'],
    'too long' => [str_repeat('1', 33)],
    'letters' => ['300-abc-1234'],
    'script' => ['<script>1234567</script>'],
    'only punctuation' => ['+(-) +(-) +(-)'],
    'few digits' => ['+57 (1) 2-3'],
]);

it('measures the length after normalization', function (): void {
    expect(PhoneNumber::fromString('  '.str_repeat('1', 32).'  ')->value())->toBe(str_repeat('1', 32));
});

it('compares phone numbers by value and casts to string', function (): void {
    $phone = PhoneNumber::fromString('+57 300 123 4567');

    expect($phone->equals(PhoneNumber::fromString(' +57  300 123 4567 ')))->toBeTrue()
        ->and($phone->equals(PhoneNumber::fromString('+57 300 123 4568')))->toBeFalse()
        ->and((string) $phone)->toBe('+57 300 123 4567');
});

it('exposes a non throwing validity check', function (): void {
    expect(PhoneNumber::isValid('+57 300 123 4567'))->toBeTrue()
        ->and(PhoneNumber::isValid('abc'))->toBeFalse();
});
