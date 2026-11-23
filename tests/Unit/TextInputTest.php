<?php

declare(strict_types=1);

use App\Http\Support\TextInput;

it('removes NUL and other control characters from single line text', function (string $raw, string $expected): void {
    expect(TextInput::singleLine($raw))->toBe($expected);
})->with([
    'nul' => ["Ada\0Lovelace", 'AdaLovelace'],
    'bell and escape' => ["A\x07d\x1Ba", 'Ada'],
    'delete' => ["Ada\x7F", 'Ada'],
    'newline and tab become a space' => ["Ada\nLove\tlace", 'Ada Love lace'],
    'carriage return' => ["Ada\r\nLovelace", 'Ada  Lovelace'],
    'surrounding whitespace' => ['  Ada  ', 'Ada'],
    'accents are kept' => ['Ñandú — Ünïcode ✓', 'Ñandú — Ünïcode ✓'],
]);

it('keeps newlines and tabs in multi line text but drops other control characters', function (): void {
    expect(TextInput::multiLine("  line 1\r\nline\t2\rline 3\0\x01\x7F  "))->toBe("line 1\nline\t2\nline 3");
});

it('replaces invalid UTF-8 so the value can be encoded as JSON', function (): void {
    $clean = TextInput::singleLine("Ada\xC3");

    expect(mb_check_encoding($clean, 'UTF-8'))->toBeTrue()
        ->and(json_encode($clean, JSON_THROW_ON_ERROR))->toBeString()
        ->and(TextInput::multiLine("a\xC3\xC3b"))->toStartWith('a')->toEndWith('b');
});

it('treats Unicode separators as whitespace', function (): void {
    expect(TextInput::singleLine("\u{00A0}Ada\u{3000}"))->toBe('Ada')
        ->and(TextInput::singleLine("\u{00A0}\u{2003}"))->toBe('')
        ->and(TextInput::multiLine("\u{00A0}a\u{00A0}b\u{00A0}"))->toBe('a b');
});
