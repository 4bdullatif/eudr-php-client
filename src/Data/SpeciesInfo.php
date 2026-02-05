<?php

declare(strict_types=1);

namespace Eudr\Data;

use Eudr\Exceptions\ValidationException;

final readonly class SpeciesInfo
{
    public function __construct(
        public ?string $scientificName = null,
        public ?string $commonName = null,
    ) {
    }

    public function validate(): void
    {
        if ($this->scientificName === null && $this->commonName === null) {
            throw new ValidationException('SpeciesInfo must have at least a scientific name or a common name');
        }
    }
}
