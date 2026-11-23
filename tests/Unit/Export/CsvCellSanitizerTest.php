<?php

declare(strict_types=1);

use App\Infrastructure\Export\CsvCellSanitizer;

it('prefixes cells starting with = + - @ tab or carriage return with a quote', function (string $cell): void {
    expect(CsvCellSanitizer::sanitize($cell))->toBe("'".$cell);
})->with([
    'equals' => '=SUM(A1:A9)',
    'plus' => '+34 600 123 456',
    'minus' => '-2+3',
    'at' => '@SUM(1)',
    'tab' => "\t=1+1",
    'carriage return' => "\r=1+1",
    'line feed' => "\n=1+1",
    'nul' => "\0=1+1",
    'hyperlink' => '=HYPERLINK("http://evil.test","click")',
    'spaces before the payload' => '   =1+1',
    'tab then spaces before the payload' => " \t +cmd|' /C calc'!A0",
    'no-break space before the payload' => "\u{00A0}@evil",
    'full-width spaces before the payload' => "\u{3000}\u{2003}-1",
]);

it('leaves safe cells untouched', function (string $cell): void {
    expect(CsvCellSanitizer::sanitize($cell))->toBe($cell);
})->with([
    'plain' => 'Ada Lovelace',
    'email' => 'ada@example.test',
    'dash inside' => 'Perez-Gomez',
    'equals inside' => 'a=b',
    'plus inside' => '1+1',
    'digits' => '600123456',
    'empty' => '',
    'accents' => 'Ñandú Ç',
    'space then letter' => '  hello',
]);

it('treats null as an empty cell', function (): void {
    expect(CsvCellSanitizer::sanitize(null))->toBe('');
});

it('is not applied twice to an already neutralized cell', function (): void {
    expect(CsvCellSanitizer::sanitize("'=1+1"))->toBe("'=1+1");
});
