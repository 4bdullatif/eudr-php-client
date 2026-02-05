<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Http\Middleware;

use Eudr\Http\Middleware\Middleware;
use Eudr\Http\Middleware\MiddlewarePipeline;
use Eudr\Tests\TestCase;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class MiddlewarePipelineTest extends TestCase
{
    public function testEmptyPipelineCallsHandler(): void
    {
        $pipeline = new MiddlewarePipeline();
        $request = new Request('POST', 'https://example.com');
        $expectedResponse = new Response(200);

        $response = $pipeline->handle($request, fn () => $expectedResponse);

        $this->assertSame($expectedResponse, $response);
    }

    public function testMiddlewareExecutedInOrder(): void
    {
        $order = [];

        $middleware1 = new class ($order) implements Middleware {
            /** @param list<string> $order */
            public function __construct(private array &$order)
            {
            }

            public function process(RequestInterface $request, callable $next): ResponseInterface
            {
                $this->order[] = 'before1';
                $response = $next($request);
                $this->order[] = 'after1';
                return $response;
            }
        };

        $middleware2 = new class ($order) implements Middleware {
            /** @param list<string> $order */
            public function __construct(private array &$order)
            {
            }

            public function process(RequestInterface $request, callable $next): ResponseInterface
            {
                $this->order[] = 'before2';
                $response = $next($request);
                $this->order[] = 'after2';
                return $response;
            }
        };

        $pipeline = new MiddlewarePipeline();
        $pipeline->add($middleware1);
        $pipeline->add($middleware2);

        $request = new Request('POST', 'https://example.com');
        $pipeline->handle($request, fn () => new Response(200));

        $this->assertSame(['before1', 'before2', 'after2', 'after1'], $order);
    }

    public function testMiddlewareCanModifyRequest(): void
    {
        $middleware = new class () implements Middleware {
            public function process(RequestInterface $request, callable $next): ResponseInterface
            {
                $modifiedRequest = $request->withHeader('X-Custom', 'test');
                return $next($modifiedRequest);
            }
        };

        $pipeline = new MiddlewarePipeline();
        $pipeline->add($middleware);

        $capturedRequest = null;
        $request = new Request('POST', 'https://example.com');

        $pipeline->handle($request, function (RequestInterface $req) use (&$capturedRequest) {
            $capturedRequest = $req;
            return new Response(200);
        });

        $this->assertNotNull($capturedRequest);
        $this->assertSame('test', $capturedRequest->getHeaderLine('X-Custom'));
    }
}
