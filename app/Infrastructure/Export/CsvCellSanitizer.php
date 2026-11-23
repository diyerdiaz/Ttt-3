<?php

declare(strict_types=1);

namespace App\Infrastructure\Export;

/**
 * Neutralizes CSV formula injection (OWASP): a text cell a spreadsheet could evaluate as a
 * formula gets a leading apostrophe, which makes it plain text.
 *
 * Dangerous cells start with = + - @, or with tab, CR, LF or NUL, possibly after leading
 * whitespace (Excel ignores it). Phone numbers such as "+34 600 123 456" start with "+" and
 * are therefore neutralized too: a phone is never a formula, but telling apart a phone from
 * "+cmd|..." would be a fragile heuristic, so the rule is deliberately uniform and the
 * visible apostrophe is the accepted cost.
 */
final class CsvCellSanitizer
{
    private const string CONTROL_START = '/^[\t\r\n\0]/';

    private const string FORMULA_START = '/^[\s\p{Z}\0]*[=+\-@]/u';

    public static function sanitize(?string $cell): string
    {
        $cell ??= '';

        return self::isDangerous($cell) ? "'".$cell : $cell;
    }

    private static function isDangerous(string $cell): bool
    {
        if (preg_match(self::CONTROL_START, $cell) === 1) {
            return true;
        }

        // preg_match returns false on invalid UTF-8; an unreadable cell is neutralized to be safe.
        return preg_match(self::FORMULA_START, $cell) !== 0;
    }
}
