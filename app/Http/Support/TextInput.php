<?php

declare(strict_types=1);

namespace App\Http\Support;

/**
 * Cleans free text coming from the browser: invalid UTF-8 is replaced (it would break JSON
 * encoding) and control characters, NUL included, are dropped (PostgreSQL rejects or truncates them).
 */
final class TextInput
{
    public static function singleLine(string $value): string
    {
        $value = (string) preg_replace('/[\r\n\t]/u', ' ', mb_scrub($value));

        return trim((string) preg_replace('/\p{Cc}/u', '', $value));
    }

    public static function multiLine(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", mb_scrub($value));

        return trim((string) preg_replace('/[^\P{Cc}\n\t]/u', '', $value));
    }
}
