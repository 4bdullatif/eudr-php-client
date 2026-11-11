<?php

declare(strict_types=1);

namespace Eudr\Data\V3;

use Eudr\Exceptions\ValidationException;
use Eudr\Support\V3\Rules;

/**
 * Producer of a Simplified Declaration commodity (SdProducerType).
 */
final readonly class SdProducer
{
    public function __construct(
        /** Country of production, ISO 3166-1 alpha-2. */
        public string $country,
        public SdProducerLocation $location,
        public ?string $name = null,
        public ?int $position = null,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function validate(string $label = 'Producer'): void
    {
        Rules::position($this->position, "{$label} position");
        Rules::countryCode($this->country, "{$label} country");

        if ($this->name !== null) {
            Rules::length($this->name, 0, 500, "{$label} name");
        }

        $this->location->validate("{$label} location");
    }
}
