<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\EmailAddress;

it('normalizes an email address to lowercase without surrounding spaces', function (): void {
    $email = EmailAddress::fromString("  Admin@Example.COM \n");

    expect($email->value())->toBe('admin@example.com')
        ->and((string) $email)->toBe('admin@example.com');
});

it('rejects an invalid email address', function (string $value): void {
    EmailAddress::fromString($value);
})->with(['', 'not-an-email', 'a@', '@b.com', 'a b@c.com'])->throws(InvalidArgumentException::class);

it('rejects an email address longer than 254 characters', function (): void {
    EmailAddress::fromString(str_repeat('a', 250).'@b.co');
})->throws(InvalidArgumentException::class);

it('compares email addresses by value', function (): void {
    expect(EmailAddress::fromString('A@b.com')->equals(EmailAddress::fromString('a@B.com')))->toBeTrue()
        ->and(EmailAddress::fromString('a@b.com')->equals(EmailAddress::fromString('c@b.com')))->toBeFalse();
});

it('exposes the normalization used everywhere as a single source', function (): void {
    expect(EmailAddress::normalize("  MiXed@Example.COM\t"))->toBe('mixed@example.com')
        ->and(EmailAddress::normalize(''))->toBe('');
});

it('accepts an otherwise valid email of 254 characters and rejects one of 255', function (): void {
    $build = static fn (int $lastLabel): string => str_repeat('a', 64).'@'.str_repeat('b', 63).'.'.str_repeat('c', 63).'.'.str_repeat('d', $lastLabel);

    $accepted = $build(61);
    $rejected = $build(62);

    expect(strlen($accepted))->toBe(254)
        ->and(strlen($rejected))->toBe(255)
        ->and(EmailAddress::fromString($accepted)->value())->toBe($accepted);

    EmailAddress::fromString($rejected);
})->throws(InvalidArgumentException::class);
