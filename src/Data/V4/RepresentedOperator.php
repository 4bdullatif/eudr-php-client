<?php

declare(strict_types=1);

namespace Eudr\Data\V4;

use Eudr\Exceptions\ValidationException;
use Eudr\Support\V4\Rules;

/**
 * Operator on whose behalf an authorised representative (or Member State) acts
 * (common EconomicOperatorIdentificationType).
 */
final readonly class RepresentedOperator
{
    public const MAX_REFERENCE_NUMBERS = 12;

    /**
     * @param list<OperatorReferenceNumber> $referenceNumbers Up to 12 identifiers (e.g. EORI, VAT).
     */
    public function __construct(
        public string $name,
        public array $referenceNumbers = [],
        public ?OperatorAddress $address = null,
        public ?string $email = null,
        public ?string $phone = null,
        /** Abbreviated address (name and country), new in V4. Optional on submission. */
        public ?SummaryAddress $summaryAddress = null,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function validate(string $label = 'Represented operator'): void
    {
        Rules::length($this->name, 1, 200, "{$label} name");

        if (\count($this->referenceNumbers) > self::MAX_REFERENCE_NUMBERS) {
            throw new ValidationException("{$label} must not have more than " . self::MAX_REFERENCE_NUMBERS . ' reference numbers');
        }

        foreach ($this->referenceNumbers as $index => $referenceNumber) {
            $referenceNumber->validate("{$label} reference number #{$index}");
        }

        $this->address?->validate("{$label} address");
        $this->summaryAddress?->validate("{$label} summary address");

        if ($this->email !== null) {
            Rules::email($this->email, "{$label} email");
        }

        if ($this->phone !== null) {
            Rules::length($this->phone, 0, 50, "{$label} phone");
        }
    }
}
