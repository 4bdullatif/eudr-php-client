<?php

declare(strict_types=1);

namespace Eudr\Data\V4;

use Eudr\Exceptions\ValidationException;
use Eudr\Support\V4\Rules;

/**
 * Postal address of a production location in a Simplified Declaration (SdProducerPostalAddressType).
 */
final readonly class SdPostalAddress
{
    public function __construct(
        /** Street name and number. */
        public string $street,
        public string $postalCode,
        public string $city,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function validate(string $label = 'Postal address'): void
    {
        Rules::nonBlank($this->street, "{$label} street");
        Rules::length($this->street, 1, 300, "{$label} street");
        Rules::length($this->postalCode, 1, 80, "{$label} postal code");
        Rules::length($this->city, 1, 200, "{$label} city");
    }
}
