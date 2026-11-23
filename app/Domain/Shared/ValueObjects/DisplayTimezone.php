<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObjects;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Single point where stored UTC moments become the user's local time and back.
 */
final class DisplayTimezone
{
    public static function name(): string
    {
        $name = config('app.display_timezone');

        return is_string($name) && $name !== '' ? $name : throw new InvalidArgumentException('app.display_timezone must be a timezone name.');
    }

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::name());
    }

    public static function toDisplay(CarbonInterface $moment): CarbonImmutable
    {
        return CarbonImmutable::instance($moment)->setTimezone(self::name());
    }

    /**
     * Parses a wall-clock value typed by the user (in the display zone) and returns it in UTC.
     */
    public static function fromLocal(string $format, string $value): CarbonImmutable
    {
        $local = CarbonImmutable::createFromFormat('!'.$format, $value, self::name())
            ?: throw new InvalidArgumentException("\"{$value}\" does not match the format \"{$format}\".");

        return $local->utc();
    }
}
