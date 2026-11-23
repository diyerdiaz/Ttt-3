<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Reads CSV text the way a spreadsheet would: drops the UTF-8 BOM and parses with fgetcsv.
 */
final class CsvDownload
{
    public const string BOM = "\xEF\xBB\xBF";

    /**
     * @return list<list<string|null>>
     */
    public static function rows(string $body): array
    {
        $stream = fopen('php://memory', 'w+b');
        assert($stream !== false);
        fwrite($stream, str_starts_with($body, self::BOM) ? substr($body, strlen(self::BOM)) : $body);
        rewind($stream);

        $rows = [];

        while (($row = fgetcsv($stream, escape: '')) !== false) {
            $rows[] = array_values($row);
        }

        fclose($stream);

        return $rows;
    }
}
