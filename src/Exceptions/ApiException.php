<?php

declare(strict_types=1);

namespace Eudr\Exceptions;

use Eudr\Responses\ErrorResponse;

final class ApiException extends EudrException
{
    public function __construct(
        string $message,
        public readonly ?ErrorResponse $errorResponse = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
