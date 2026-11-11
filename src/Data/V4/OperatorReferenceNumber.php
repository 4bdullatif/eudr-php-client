<?php

declare(strict_types=1);

namespace Eudr\Data\V4;

use Eudr\Enums\V4\IdentifierType;
use Eudr\Exceptions\ValidationException;
use Eudr\Support\V4\Rules;

/**
 * Identifier of an economic operator (EconomicOperatorReferenceNumberType).
 */
final readonly class OperatorReferenceNumber
{
    public function __construct(
        public IdentifierType $type,
        public string $value,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function validate(string $label = 'Operator reference number'): void
    {
        Rules::nonBlank($this->value, "{$label} value");
        Rules::length($this->value, 0, 80, "{$label} value");
    }
}
