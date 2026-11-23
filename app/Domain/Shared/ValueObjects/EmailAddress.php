<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;
use Stringable;

final readonly class EmailAddress implements Stringable
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = self::normalize($value);

        // FILTER_VALIDATE_EMAIL already enforces the 254 character limit (see the boundary test).
        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Invalid email address.');
        }

        return new self($normalized);
    }

    public static function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
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
}
