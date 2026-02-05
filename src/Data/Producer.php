<?php

declare(strict_types=1);

namespace Eudr\Data;

use Eudr\Exceptions\ValidationException;
use Eudr\Requests\Builders\ProducerBuilder;

final readonly class Producer
{
    public static function make(): ProducerBuilder
    {
        return new ProducerBuilder();
    }

    public function __construct(
        public string $country,
        public string $geometryGeojson,
        public ?string $name = null,
    ) {
    }

    public function validate(): void
    {
        if ($this->country === '') {
            throw new ValidationException('Producer country is required');
        }

        if (!preg_match('/^[A-Z]{2}$/', $this->country)) {
            throw new ValidationException('Producer country must be a valid ISO 3166-1 alpha-2 code (e.g., "DE", "FR")');
        }

        if ($this->geometryGeojson === '') {
            throw new ValidationException('Producer geometryGeojson is required');
        }

        if ($this->name !== null && mb_strlen($this->name) > 500) {
            throw new ValidationException('Producer name must not exceed 500 characters');
        }
    }
}
