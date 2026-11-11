<?php

declare(strict_types=1);

namespace Eudr\Data\V3;

use Eudr\Exceptions\ValidationException;
use Eudr\Support\V3\Rules;

/**
 * Structured address of a represented operator (common AddressType).
 */
final readonly class OperatorAddress
{
    public function __construct(
        /** ISO 3166-1 alpha-2 country code. */
        public string $country,
        /** Street name and number. */
        public string $street,
        public string $postalCode,
        public string $city,
        public ?string $fullAddress = null,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function validate(string $label = 'Operator address'): void
    {
        Rules::countryCode($this->country, "{$label} country");
        Rules::length($this->street, 1, 300, "{$label} street");
        Rules::length($this->postalCode, 1, 80, "{$label} postal code");
        Rules::length($this->city, 1, 200, "{$label} city");

        if ($this->fullAddress !== null) {
            Rules::length($this->fullAddress, 1, 250, "{$label} full address");
        }
    }
}
