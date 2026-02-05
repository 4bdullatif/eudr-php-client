<?php

declare(strict_types=1);

namespace Eudr\Http\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class MiddlewarePipeline
{
    /** @var list<Middleware> */
    private array $middleware = [];

    /**
     * @internal The pipeline should not be modified after construction.
     */
    public function add(Middleware $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * @param callable(RequestInterface): ResponseInterface $handler
     * @return callable(RequestInterface): ResponseInterface
     */
    public function build(callable $handler): callable
    {
        $reducer = static function (callable $next, Middleware $middleware): callable {
            return static fn (RequestInterface $request): ResponseInterface => $middleware->process($request, $next);
        };

        return array_reduce(array_reverse($this->middleware), $reducer, $handler);
    }

    /**
     * @param callable(RequestInterface): ResponseInterface $handler
     */
    public function handle(RequestInterface $request, callable $handler): ResponseInterface
    {
        $pipeline = $this->build($handler);
        return $pipeline($request);
    }
}
