<?php

declare(strict_types=1);

namespace App\Domain\Activities\ValueObjects;

use App\Domain\Activities\Enums\ActivityType;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * What the user records about a contact with a lead. It lives in Domain because the repository
 * contract accepts it and Domain must not depend on Application. Cleaning of text coming from
 * the browser happens at the HTTP edge; here only the invariants are enforced.
 */
final readonly class ActivityData
{
    public const int DESCRIPTION_MAX_LENGTH = 5000;

    public function __construct(
        public string $description,
        public CarbonImmutable $occurredAt,
        public ?ActivityType $type = null,
    ) {
        if ((preg_replace('/[\s\p{Z}]+/u', '', $description) ?? $description) === '') {
            throw new InvalidArgumentException('The activity description must not be blank.');
        }

        if (mb_strlen($description) > self::DESCRIPTION_MAX_LENGTH) {
            throw new InvalidArgumentException('The activity description must not exceed '.self::DESCRIPTION_MAX_LENGTH.' characters.');
        }

        if (str_contains($description, "\0")) {
            throw new InvalidArgumentException('The activity description must not contain NUL bytes.');
        }
    }
}
