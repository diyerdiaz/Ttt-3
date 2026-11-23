<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Monolog\Formatter\JsonFormatter;
use Tests\Support\Accounts;

beforeEach(function (): void {
    $this->logDirectory = sys_get_temp_dir().'/security-log-'.bin2hex(random_bytes(4));
    mkdir($this->logDirectory);
    config(['logging.channels.security.path' => $this->logDirectory.'/security.log']);
    Log::forgetChannel('security');
});

afterEach(function (): void {
    (new Filesystem)->deleteDirectory($this->logDirectory);
});

/**
 * @return list<string>
 */
function securityLogLines(string $directory): array
{
    $lines = [];

    foreach (glob($directory.'/*.log') ?: [] as $file) {
        $lines = [...$lines, ...file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []];
    }

    return $lines;
}

it('writes one JSON line per event even when the user agent contains line breaks', function (): void {
    $this->withServerVariables(['HTTP_USER_AGENT' => "evil\n[2026-01-01] security.INFO: auth.login {\"user_id\":1}\r\nmore"])
        ->post('/login', ['email' => 'ada@example.com', 'password' => Accounts::WRONG_PASSWORD]);

    $lines = securityLogLines($this->logDirectory);

    expect($lines)->toHaveCount(1)
        ->and(json_decode($lines[0], true, flags: JSON_THROW_ON_ERROR)['message'])->toBe('auth.login_failed');
});

it('truncates the logged user agent to 512 characters', function (): void {
    $this->withServerVariables(['HTTP_USER_AGENT' => str_repeat('x', 600)])
        ->post('/login', ['email' => 'ada@example.com', 'password' => Accounts::WRONG_PASSWORD]);

    $entry = json_decode(securityLogLines($this->logDirectory)[0], true, flags: JSON_THROW_ON_ERROR);

    expect(mb_strlen($entry['context']['user_agent']))->toBe(512);
});

it('configures the security channel as daily rotating JSON', function (): void {
    expect(config('logging.channels.security.driver'))->toBe('daily')
        ->and(config('logging.channels.security.formatter'))->toBe(JsonFormatter::class)
        ->and(config('logging.channels.security.days'))->toBeInt()->toBeGreaterThan(0);
});
