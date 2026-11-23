<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\DisplayTimezone;
use Carbon\CarbonImmutable;
use Tests\Support\ConfigEnv;

it('keeps storing in UTC while displaying in Colombia by default', function (): void {
    expect(config('app.timezone'))->toBe('UTC')
        ->and(config('database.connections.pgsql.timezone'))->toBe('UTC')
        ->and(config('app.display_timezone'))->toBe('America/Bogota');
});

it('falls back to America/Bogota when APP_DISPLAY_TIMEZONE is unset or empty', function (?string $value): void {
    expect(ConfigEnv::evaluate('app', ['APP_DISPLAY_TIMEZONE' => $value])['display_timezone'])->toBe('America/Bogota');
})->with(['unset' => [null], 'empty' => ['']]);

it('reads APP_DISPLAY_TIMEZONE and rejects unknown zones', function (): void {
    expect(ConfigEnv::evaluate('app', ['APP_DISPLAY_TIMEZONE' => 'Europe/Madrid'])['display_timezone'])->toBe('Europe/Madrid')
        ->and(fn () => ConfigEnv::evaluate('app', ['APP_DISPLAY_TIMEZONE' => 'Mars/Olympus']))->toThrow(InvalidArgumentException::class);
});

it('shows a UTC moment in the display zone', function (): void {
    $shown = DisplayTimezone::toDisplay(CarbonImmutable::parse('2026-11-23 15:00:00', 'UTC'));

    expect($shown->format('Y-m-d H:i'))->toBe('2026-11-23 10:00')
        ->and($shown->toIso8601String())->toBe('2026-11-23T10:00:00-05:00');
});

it('interprets local input in the display zone and returns UTC', function (): void {
    $moment = DisplayTimezone::fromLocal('Y-m-d\TH:i', '2026-11-23T10:00');

    expect($moment->timezoneName)->toBe('UTC')
        ->and($moment->format('Y-m-d H:i:s'))->toBe('2026-11-23 15:00:00');
});

it('gives the current moment in the display zone', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-23 03:00:00', 'UTC'));

    expect(DisplayTimezone::now()->format('Y-m-d H:i'))->toBe('2026-11-22 22:00');
});

it('follows the configured display zone', function (): void {
    config()->set('app.display_timezone', 'Asia/Tokyo');

    expect(DisplayTimezone::toDisplay(CarbonImmutable::parse('2026-11-23 15:00:00', 'UTC'))->format('H:i'))->toBe('00:00');
});
