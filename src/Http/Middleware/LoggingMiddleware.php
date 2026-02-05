<?php

declare(strict_types=1);

namespace Eudr\Http\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final readonly class LoggingMiddleware implements Middleware
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $this->logger->info('EUDR API Request', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
        ]);

        $startTime = microtime(true);

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;
            $this->logger->error('EUDR API Request failed', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'duration_ms' => round($duration * 1000, 2),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $duration = microtime(true) - $startTime;

        $this->logger->info('EUDR API Response', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'status' => $response->getStatusCode(),
            'duration_ms' => round($duration * 1000, 2),
        ]);

        return $response;
    }
}
