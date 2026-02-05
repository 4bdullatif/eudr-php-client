<?php

declare(strict_types=1);

namespace Eudr\Exceptions;

class HttpException extends EudrException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly string $responseBody = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
