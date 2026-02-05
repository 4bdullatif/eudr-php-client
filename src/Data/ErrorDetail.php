<?php

declare(strict_types=1);

namespace Eudr\Data;

final readonly class ErrorDetail
{
    public function __construct(
        public string $id,
        public ?string $message = null,
        public ?string $field = null,
    ) {
    }
}
