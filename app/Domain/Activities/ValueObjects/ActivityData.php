<?php

declare(strict_types=1);

namespace App\Domain\Activities\ValueObjects;

use App\Domain\Activities\Enums\ActivityType;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * What the user records about a contact with a lead. It lives in Domain because the repository
 * contract accepts it and Domain must not depend on Application. The browser text is cleaned at
 * the HTTP edge (`TextInput::multiLine`); the VO rejects whatever that cleaning would have removed,
 * so no other entry point can store invalid UTF-8 or control characters.
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

        if (! mb_check_encoding($description, 'UTF-8') || preg_match('/[^\P{Cc}\n\t]/u', $description) === 1) {
            throw new InvalidArgumentException('The activity description must be clean UTF-8 text without control characters.');
        }
    }
}
