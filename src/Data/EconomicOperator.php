<?php

declare(strict_types=1);

namespace Eudr\Data;

use Eudr\Exceptions\ValidationException;
use Eudr\Requests\Builders\OperatorBuilder;

final readonly class EconomicOperator
{
    public static function make(): OperatorBuilder
    {
        return new OperatorBuilder();
    }

    /**
     * @param list<array{identifierType: string, identifierValue: string}> $referenceNumbers
     */
    public function __construct(
        public ?string $name = null,
        public ?Address $address = null,
        public ?string $email = null,
        public ?string $phone = null,
        public array $referenceNumbers = [],
    ) {
    }

    public function validate(): void
    {
        if ($this->referenceNumbers === []) {
            throw new ValidationException('EconomicOperator must have at least one reference number');
        }

        foreach ($this->referenceNumbers as $index => $ref) {
            if ($ref['identifierType'] === '') {
                throw new ValidationException("Reference number at index {$index} must have a non-empty identifierType");
            }

            if ($ref['identifierValue'] === '') {
                throw new ValidationException("Reference number at index {$index} must have a non-empty identifierValue");
            }
        }
    }
}
