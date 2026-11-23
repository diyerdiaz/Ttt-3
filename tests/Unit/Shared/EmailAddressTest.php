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
