<?php

declare(strict_types=1);

namespace Eudr\Http\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Middleware
{
    /**
     * @param callable(RequestInterface): ResponseInterface $next
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface;
}
