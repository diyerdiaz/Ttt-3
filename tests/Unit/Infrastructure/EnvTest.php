<?php

declare(strict_types=1);

use App\Infrastructure\Support\Env;

it('treats only null and empty strings as unset when reading integers', function (?string $value, int $expected): void {
    $_ENV['ENV_PROBE'] = $_SERVER['ENV_PROBE'] = $value;

    try {
        expect(Env::int('ENV_PROBE', 7))->toBe($expected);
    } finally {
        unset($_ENV['ENV_PROBE'], $_SERVER['ENV_PROBE']);
    }
})->with([
    'null' => [null, 7],
    'empty' => ['', 7],
    'zero' => ['0', 0],
    'number' => ['42', 42],
]);
