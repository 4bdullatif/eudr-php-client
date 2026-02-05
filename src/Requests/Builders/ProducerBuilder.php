<?php

declare(strict_types=1);

namespace Eudr\Requests\Builders;

use Eudr\Data\Producer;

/**
 * Fluent builder for constructing Producer value objects.
 */
final class ProducerBuilder
{
    private string $country = '';
    private string $geometryGeojson = '';
    private ?string $name = null;

    public function country(string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function geometryGeojson(string $geometryGeojson): self
    {
        $this->geometryGeojson = $geometryGeojson;
        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function build(): Producer
    {
        return new Producer(
            country: $this->country,
            geometryGeojson: $this->geometryGeojson,
            name: $this->name,
        );
    }
}
