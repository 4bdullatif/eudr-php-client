<?php

declare(strict_types=1);

namespace Eudr\Http\Middleware;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class RetryMiddleware implements Middleware
{
    /** @var \Closure(int): void */
    private \Closure $sleeper;

    /**
     * @param \Closure(int): void|null $sleeper Custom sleep function receiving microseconds
     */
    public function __construct(
        private int $maxAttempts = 3,
        private int $baseDelayMs = 100,
        ?\Closure $sleeper = null,
    ) {
        if ($sleeper !== null) {
            $this->sleeper = $sleeper;
        } else {
            $this->sleeper = static function (int $microseconds): void {
                usleep($microseconds);
            };
        }
    }

    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxAttempts) {
            try {
                $response = $next($request);

                if ($response->getStatusCode() < 500) {
                    return $response;
                }

                // Server error - retry
                if ($attempt + 1 >= $this->maxAttempts) {
                    return $response;
                }
            } catch (ClientExceptionInterface $e) {
                $lastException = $e;

                if ($attempt + 1 >= $this->maxAttempts) {
                    throw $e;
                }
            }

            $attempt++;
            $delayMs = $this->baseDelayMs * (2 ** ($attempt - 1));
            ($this->sleeper)($delayMs * 1000);

            // Before retrying, rewind the request body if seekable
            $requestBody = $request->getBody();
            if ($requestBody->isSeekable()) {
                $requestBody->rewind();
            }
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        throw new \LogicException('Unreachable: retry loop exited without returning.');
    }
}
