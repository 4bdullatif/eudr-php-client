<?php

declare(strict_types=1);

namespace Eudr\Data\V4;

use Eudr\Exceptions\ValidationException;
use Eudr\Support\V4\Rules;

/**
 * Abbreviated operator address: name and country only (SummaryAddressType, new in V4).
 *
 * Optional on submission. The Information System returns represented operators with this
 * abbreviated address.
 */
final readonly class SummaryAddress
{
    public function __construct(
        public ?string $name = null,
        /** ISO 3166-1 alpha-2 country code. */
        public ?string $country = null,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function validate(string $label = 'Summary address'): void
    {
        if ($this->name !== null) {
            Rules::length($this->name, 1, 200, "{$label} name");
        }

        if ($this->country !== null) {
            Rules::countryCode($this->country, "{$label} country");
        }
    }
}
