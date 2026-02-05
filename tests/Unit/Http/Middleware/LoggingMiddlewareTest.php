<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Http\Middleware;

use Eudr\Http\Middleware\LoggingMiddleware;
use Eudr\Tests\Support\TestLogger;
use Eudr\Tests\TestCase;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

final class LoggingMiddlewareTest extends TestCase
{
    private TestLogger $logger;
    private LoggingMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new TestLogger();
        $this->middleware = new LoggingMiddleware($this->logger);
    }

    public function testLogsRequestUriAndMethod(): void
    {
        $request = new Request('POST', 'https://example.com/api/v1/submit');
        $next = static fn (): Response => new Response(200);

        $this->middleware->process($request, $next);

        $requestLog = $this->findLog('info', 'EUDR API Request');
        $this->assertNotNull($requestLog, 'Expected a request log entry');
        $this->assertSame('POST', $requestLog['context']['method']);
        $this->assertSame('https://example.com/api/v1/submit', $requestLog['context']['uri']);
    }

    public function testLogsResponseStatusCode(): void
    {
        $request = new Request('POST', 'https://example.com/api/v1/submit');
        $next = static fn (): Response => new Response(200);

        $this->middleware->process($request, $next);

        $responseLog = $this->findLog('info', 'EUDR API Response');
        $this->assertNotNull($responseLog, 'Expected a response log entry');
        $this->assertSame(200, $responseLog['context']['status']);
    }

    public function testLogsResponseDuration(): void
    {
        $request = new Request('POST', 'https://example.com/api/v1/submit');
        $next = static fn (): Response => new Response(200);

        $this->middleware->process($request, $next);

        $responseLog = $this->findLog('info', 'EUDR API Response');
        $this->assertNotNull($responseLog, 'Expected a response log entry');
        $this->assertArrayHasKey('duration_ms', $responseLog['context']);
        $this->assertIsFloat($responseLog['context']['duration_ms']);
        $this->assertGreaterThanOrEqual(0, $responseLog['context']['duration_ms']);
    }

    public function testLogsErrorOnException(): void
    {
        $request = new Request('POST', 'https://example.com/api/v1/submit');
        $exception = new \RuntimeException('Connection failed');
        $next = static function () use ($exception): never {
            throw $exception;
        };

        try {
            $this->middleware->process($request, $next);
            $this->fail('Expected exception to be rethrown');
        } catch (\RuntimeException) {
            // expected
        }

        $errorLog = $this->findLog('error', 'EUDR API Request failed');
        $this->assertNotNull($errorLog, 'Expected an error log entry');
        $this->assertSame('POST', $errorLog['context']['method']);
        $this->assertSame('https://example.com/api/v1/submit', $errorLog['context']['uri']);
        $this->assertSame('Connection failed', $errorLog['context']['error']);
        $this->assertArrayHasKey('duration_ms', $errorLog['context']);
    }

    public function testReturnsResponseFromNext(): void
    {
        $request = new Request('POST', 'https://example.com/api/v1/submit');
        $expectedResponse = new Response(201, ['X-Custom' => 'test']);
        $next = static fn (): Response => $expectedResponse;

        $actualResponse = $this->middleware->process($request, $next);

        $this->assertSame($expectedResponse, $actualResponse);
    }

    /**
     * @return array{level: string, message: string, context: array<string, mixed>}|null
     */
    private function findLog(string $level, string $message): ?array
    {
        foreach ($this->logger->logs as $log) {
            if ($log['level'] === $level && $log['message'] === $message) {
                return $log;
            }
        }

        return null;
    }
}
