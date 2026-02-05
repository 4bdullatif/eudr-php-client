<?php

declare(strict_types=1);

namespace Eudr\Tests\Support;

use Psr\Log\AbstractLogger;

/**
 * Simple in-memory PSR-3 logger for testing.
 *
 * @phpstan-type LogEntry array{level: string, message: string, context: array<string, mixed>}
 */
final class TestLogger extends AbstractLogger
{
    /** @var list<LogEntry> */
    public array $logs = [];

    /**
     * @param mixed $level
     * @param array<string, mixed> $context
     */
    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}
