<?php

declare(strict_types=1);

namespace Tests\Support;

use Psr\Log\LoggerInterface;

final class AuditLog
{
    /**
     * Asserts the spied channel received $message at $level and returns its context.
     *
     * @return array<string, mixed>
     */
    public static function context(LoggerInterface $channel, string $level, string $message): array
    {
        $captured = null;
        $channel->shouldHaveReceived($level, function (string $actual, array $context) use ($message, &$captured): bool {
            $captured = $context;

            return $actual === $message;
        });

        return $captured ?? [];
    }
}
