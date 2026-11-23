<?php

declare(strict_types=1);

namespace App\Infrastructure\Support;

use Illuminate\Support\Env as LaravelEnv;
use InvalidArgumentException;

/**
 * Typed env readers for config files. Only null and '' (a bare `KEY=` in .env) count as
 * unset, so an explicit 0 or false is respected.
 */
final class Env
{
    public static function int(string $key, int $default): int
    {
        $value = LaravelEnv::get($key);

        return is_int($value) || (is_string($value) && is_numeric($value)) ? (int) $value : $default;
    }

    public static function bool(string $key, bool $default): bool
    {
        $value = LaravelEnv::get($key);

        return $value === null || $value === '' ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function timezone(string $key, string $default): string
    {
        $value = LaravelEnv::get($key);
        $zone = is_string($value) && $value !== '' ? $value : $default;

        return in_array($zone, timezone_identifiers_list(), true)
            ? $zone
            : throw new InvalidArgumentException("The {$key} value \"{$zone}\" is not a valid timezone.");
    }
}
