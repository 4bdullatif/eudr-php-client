<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Http\Middleware;

use Eudr\Http\Middleware\RetryMiddleware;
use Eudr\Tests\TestCase;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;

final class RetryMiddlewareTest extends TestCase
{
    public function testDoesNotRetryOnSuccess(): void
    {
        $middleware = new RetryMiddleware(maxAttempts: 3, baseDelayMs: 0);
        $callCount = 0;

        $request = new Request('POST', 'https://example.com');
        $response = $middleware->process($request, function () use (&$callCount) {
            $callCount++;
            return new Response(200);
        });

        $this->assertSame(1, $callCount);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRetriesOnServerError(): void
    {
        $middleware = new RetryMiddleware(maxAttempts: 3, baseDelayMs: 0);
        $callCount = 0;

        $request = new Request('POST', 'https://example.com');
        $response = $middleware->process($request, function () use (&$callCount) {
            $callCount++;
            if ($callCount < 3) {
                return new Response(500);
            }
            return new Response(200);
        });

        $this->assertSame(3, $callCount);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDoesNotRetryOnClientError(): void
    {
        $middleware = new RetryMiddleware(maxAttempts: 3, baseDelayMs: 0);
        $callCount = 0;

        $request = new Request('POST', 'https://example.com');
        $response = $middleware->process($request, function () use (&$callCount) {
            $callCount++;
            return new Response(400);
        });

        $this->assertSame(1, $callCount);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRetriesOnPsr18Exception(): void
    {
        $middleware = new RetryMiddleware(maxAttempts: 3, baseDelayMs: 0);
        $callCount = 0;

        $request = new Request('POST', 'https://example.com');
        $response = $middleware->process($request, function () use (&$callCount) {
            $callCount++;
            if ($callCount < 2) {
                throw self::createClientException('Network error');
            }
            return new Response(200);
        });

        $this->assertSame(2, $callCount);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testThrowsAfterMaxRetries(): void
    {
        $middleware = new RetryMiddleware(maxAttempts: 2, baseDelayMs: 0);

        $request = new Request('POST', 'https://example.com');

        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionMessage('Network error');

        $middleware->process($request, function (RequestInterface $req): void {
            throw self::createClientException('Network error');
        });
    }

    public function testDoesNotRetryNonPsr18Exceptions(): void
    {
        $middleware = new RetryMiddleware(maxAttempts: 3, baseDelayMs: 0);
        $callCount = 0;

        $request = new Request('POST', 'https://example.com');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Validation error');

        $middleware->process($request, function () use (&$callCount): void {
            $callCount++;
            throw new \RuntimeException('Validation error');
        });

        // Should not have retried - only called once
        $this->assertSame(1, $callCount);
    }

    public function testReturnsLastResponseAfterMaxRetries(): void
    {
        $middleware = new RetryMiddleware(maxAttempts: 2, baseDelayMs: 0);

        $request = new Request('POST', 'https://example.com');
        $response = $middleware->process($request, function () {
            return new Response(503);
        });

        $this->assertSame(503, $response->getStatusCode());
    }

    public function testExponentialBackoffDelayValues(): void
    {
        /** @var list<int> $sleepCalls */
        $sleepCalls = [];
        $sleeper = static function (int $microseconds) use (&$sleepCalls): void {
            $sleepCalls[] = $microseconds;
        };

        $middleware = new RetryMiddleware(
            maxAttempts: 4,
            baseDelayMs: 100,
            sleeper: $sleeper,
        );

        $callCount = 0;
        $request = new Request('POST', 'https://example.com');
        $response = $middleware->process($request, function () use (&$callCount) {
            $callCount++;
            if ($callCount < 4) {
                return new Response(500);
            }
            return new Response(200);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(4, $callCount);

        // 3 retries = 3 sleep calls with exponential backoff
        // Attempt 1 fails -> sleep(100ms * 2^0 = 100ms = 100_000 us)
        // Attempt 2 fails -> sleep(100ms * 2^1 = 200ms = 200_000 us)
        // Attempt 3 fails -> sleep(100ms * 2^2 = 400ms = 400_000 us)
        $this->assertCount(3, $sleepCalls);
        $this->assertSame(100_000, $sleepCalls[0]);
        $this->assertSame(200_000, $sleepCalls[1]);
        $this->assertSame(400_000, $sleepCalls[2]);
    }

    public function testExponentialBackoffOnExceptions(): void
    {
        /** @var list<int> $sleepCalls */
        $sleepCalls = [];
        $sleeper = static function (int $microseconds) use (&$sleepCalls): void {
            $sleepCalls[] = $microseconds;
        };

        $middleware = new RetryMiddleware(
            maxAttempts: 3,
            baseDelayMs: 50,
            sleeper: $sleeper,
        );

        $callCount = 0;
        $request = new Request('POST', 'https://example.com');
        $response = $middleware->process($request, function () use (&$callCount) {
            $callCount++;
            if ($callCount < 3) {
                throw self::createClientException('Network error');
            }
            return new Response(200);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(3, $callCount);

        // 2 retries = 2 sleep calls
        // Attempt 1 fails -> sleep(50ms * 2^0 = 50ms = 50_000 us)
        // Attempt 2 fails -> sleep(50ms * 2^1 = 100ms = 100_000 us)
        $this->assertCount(2, $sleepCalls);
        $this->assertSame(50_000, $sleepCalls[0]);
        $this->assertSame(100_000, $sleepCalls[1]);
    }

    public function testNoSleepOnSuccessFirstAttempt(): void
    {
        /** @var list<int> $sleepCalls */
        $sleepCalls = [];
        $sleeper = static function (int $microseconds) use (&$sleepCalls): void {
            $sleepCalls[] = $microseconds;
        };

        $middleware = new RetryMiddleware(
            maxAttempts: 3,
            baseDelayMs: 100,
            sleeper: $sleeper,
        );

        $request = new Request('POST', 'https://example.com');
        $response = $middleware->process($request, function () {
            return new Response(200);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(0, $sleepCalls);
    }

    /**
     * Create a PSR-18 ClientExceptionInterface for testing.
     */
    private static function createClientException(string $message): ClientExceptionInterface
    {
        return new class ($message) extends \RuntimeException implements ClientExceptionInterface {};
    }
}
