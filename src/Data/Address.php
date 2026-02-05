<?php

declare(strict_types=1);

namespace Eudr\Data;

final readonly class Address
{
    public function __construct(
        public ?string $street = null,
        public ?string $number = null,
        public ?string $postcode = null,
        public ?string $city = null,
        public ?string $countryCode = null,
        public ?string $fullAddress = null,
    ) {
    }
}
