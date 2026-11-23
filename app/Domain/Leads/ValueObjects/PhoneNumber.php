<?php

declare(strict_types=1);

namespace App\Domain\Leads\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class PhoneNumber implements Stringable
{
    public const int MAX_LENGTH = 32;

    private const int MIN_LENGTH = 6;

    private const int MIN_DIGITS = 6;

    private const string ALLOWED = '/^[+\d\s()-]+$/';

    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = self::normalize($value);

        if (! self::satisfiesRules($normalized)) {
            throw new InvalidArgumentException('Invalid phone number.');
        }

        return new self($normalized);
    }

    public static function isValid(string $value): bool
    {
        return self::satisfiesRules(self::normalize($value));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function normalize(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private static function satisfiesRules(string $normalized): bool
    {
        $length = strlen($normalized);

        return $length >= self::MIN_LENGTH
            && $length <= self::MAX_LENGTH
            && preg_match(self::ALLOWED, $normalized) === 1
            && strlen((string) preg_replace('/\D/', '', $normalized)) >= self::MIN_DIGITS;
    }
}
