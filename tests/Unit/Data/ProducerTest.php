<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Data;

use Eudr\Data\Producer;
use Eudr\Exceptions\ValidationException;
use Eudr\Requests\Builders\ProducerBuilder;
use Eudr\Tests\TestCase;

final class ProducerTest extends TestCase
{
    public function testCreatesProducer(): void
    {
        $producer = new Producer('DE', 'base64geojson', 'Test Producer');

        $this->assertSame('DE', $producer->country);
        $this->assertSame('base64geojson', $producer->geometryGeojson);
        $this->assertSame('Test Producer', $producer->name);
    }

    public function testCreatesProducerWithoutName(): void
    {
        $producer = new Producer('DE', 'geojson');

        $this->assertNull($producer->name);
    }

    public function testValidatePassesForValidProducer(): void
    {
        $producer = new Producer('DE', 'geojson');
        $producer->validate();
        $this->addToAssertionCount(1);
    }

    public function testValidateFailsForEmptyCountry(): void
    {
        $producer = new Producer('', 'geojson');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('country is required');
        $producer->validate();
    }

    public function testValidateFailsForEmptyGeometry(): void
    {
        $producer = new Producer('DE', '');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('geometryGeojson is required');
        $producer->validate();
    }

    public function testValidateFailsForLowercaseCountryCode(): void
    {
        $producer = new Producer('de', 'geojson');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('ISO 3166-1 alpha-2');
        $producer->validate();
    }

    public function testValidateFailsForThreeLetterCountryCode(): void
    {
        $producer = new Producer('DEU', 'geojson');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('ISO 3166-1 alpha-2');
        $producer->validate();
    }

    public function testMakeReturnsProducerBuilder(): void
    {
        $builder = Producer::make();
        $this->assertInstanceOf(ProducerBuilder::class, $builder);
    }

    public function testMakeBuilderBuildsProducer(): void
    {
        $producer = Producer::make()
            ->country('DE')
            ->geometryGeojson('base64geojson')
            ->name('Test Producer')
            ->build();

        $this->assertInstanceOf(Producer::class, $producer);
        $this->assertSame('DE', $producer->country);
        $this->assertSame('base64geojson', $producer->geometryGeojson);
        $this->assertSame('Test Producer', $producer->name);
    }
}
