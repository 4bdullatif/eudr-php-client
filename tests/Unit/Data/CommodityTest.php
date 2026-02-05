<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Data;

use Eudr\Data\Commodity;
use Eudr\Data\Producer;
use Eudr\Data\SpeciesInfo;
use Eudr\Exceptions\ValidationException;
use Eudr\Tests\TestCase;

final class CommodityTest extends TestCase
{
    public function testCreatesValidCommodity(): void
    {
        $commodity = new Commodity(
            position: 1,
            description: 'Test commodity',
            hsHeading: '4403',
            speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
            producers: [new Producer('DE', 'base64geojson')],
        );

        $this->assertSame(1, $commodity->position);
        $this->assertSame('Test commodity', $commodity->description);
        $this->assertSame('4403', $commodity->hsHeading);
    }

    public function testValidatePassesForValidCommodity(): void
    {
        $commodity = new Commodity(
            position: 1,
            description: 'Test commodity',
            hsHeading: '4403',
            speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
            producers: [new Producer('DE', 'base64geojson')],
        );

        $commodity->validate();
        $this->addToAssertionCount(1);
    }

    public function testValidateFailsForZeroPosition(): void
    {
        $commodity = new Commodity(
            position: 0,
            description: 'Test',
            hsHeading: '4403',
            speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
            producers: [new Producer('DE', 'geojson')],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('position must be a positive integer');
        $commodity->validate();
    }

    public function testValidateFailsForEmptyDescription(): void
    {
        $commodity = new Commodity(
            position: 1,
            description: '',
            hsHeading: '4403',
            speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
            producers: [new Producer('DE', 'geojson')],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('description is required');
        $commodity->validate();
    }

    public function testValidateFailsForLongDescription(): void
    {
        $commodity = new Commodity(
            position: 1,
            description: str_repeat('a', 513),
            hsHeading: '4403',
            speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
            producers: [new Producer('DE', 'geojson')],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('description must not exceed 512');
        $commodity->validate();
    }

    public function testValidateFailsForInvalidHsHeading(): void
    {
        $commodity = new Commodity(
            position: 1,
            description: 'Test',
            hsHeading: 'invalid',
            speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
            producers: [new Producer('DE', 'geojson')],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('hsHeading must be 2-8 digits');
        $commodity->validate();
    }

    public function testValidateAcceptsTwoDigitHsHeading(): void
    {
        $commodity = new Commodity(
            position: 1,
            description: 'Test',
            hsHeading: '44',
            speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
            producers: [new Producer('DE', 'geojson')],
        );

        $commodity->validate();
        $this->addToAssertionCount(1);
    }

    public function testValidateAcceptsEightDigitHsHeading(): void
    {
        $commodity = new Commodity(
            position: 1,
            description: 'Test',
            hsHeading: '44030020',
            speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
            producers: [new Producer('DE', 'geojson')],
        );

        $commodity->validate();
        $this->addToAssertionCount(1);
    }

    public function testValidateFailsForSingleDigitHsHeading(): void
    {
        $commodity = new Commodity(
            position: 1,
            description: 'Test',
            hsHeading: '4',
            speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
            producers: [new Producer('DE', 'geojson')],
        );

        $this->expectException(ValidationException::class);
        $commodity->validate();
    }

    public function testValidateFailsForEmptySpeciesInfo(): void
    {
        $commodity = new Commodity(
            position: 1,
            description: 'Test',
            hsHeading: '4403',
            speciesInfo: [],
            producers: [new Producer('DE', 'geojson')],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('at least one species info');
        $commodity->validate();
    }

    public function testValidateFailsForEmptyProducers(): void
    {
        $commodity = new Commodity(
            position: 1,
            description: 'Test',
            hsHeading: '4403',
            speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
            producers: [],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('between 1 and 500 producers');
        $commodity->validate();
    }

    public function testValidateFailsForLongSupplementaryUnit(): void
    {
        $commodity = new Commodity(
            position: 1,
            description: 'Test',
            hsHeading: '4403',
            supplementaryUnit: str_repeat('a', 51),
            speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
            producers: [new Producer('DE', 'geojson')],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('supplementaryUnit must not exceed 50');
        $commodity->validate();
    }

    public function testMakeReturnsCommodityBuilder(): void
    {
        $commodity = Commodity::make()
            ->position(1)
            ->description('Test commodity')
            ->hsHeading('4403')
            ->addProducer(new Producer('DE', 'geojson'))
            ->addSpeciesInfo(new SpeciesInfo('Quercus robur', 'Oak'))
            ->build();

        $this->assertSame(1, $commodity->position);
        $this->assertSame('Test commodity', $commodity->description);
        $this->assertSame('4403', $commodity->hsHeading);
        $this->assertCount(1, $commodity->producers);
        $this->assertCount(1, $commodity->speciesInfo);
        $this->assertSame('Quercus robur', $commodity->speciesInfo[0]->scientificName);
    }

    public function testValidateFailsForNegativeVolume(): void
    {
        $commodity = new Commodity(
            position: 1,
            description: 'Test',
            hsHeading: '4403',
            volume: -1.0,
            speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
            producers: [new Producer('DE', 'geojson')],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Commodity volume must not be negative');
        $commodity->validate();
    }

    public function testValidateFailsForNegativeNetWeight(): void
    {
        $commodity = new Commodity(
            position: 1,
            description: 'Test',
            hsHeading: '4403',
            netWeight: -0.5,
            speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
            producers: [new Producer('DE', 'geojson')],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Commodity netWeight must not be negative');
        $commodity->validate();
    }

    public function testValidateFailsForNegativeNumberOfUnits(): void
    {
        $commodity = new Commodity(
            position: 1,
            description: 'Test',
            hsHeading: '4403',
            numberOfUnits: -10,
            speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
            producers: [new Producer('DE', 'geojson')],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Commodity numberOfUnits must not be negative');
        $commodity->validate();
    }

    public function testCommodityWithOptionalFields(): void
    {
        $commodity = new Commodity(
            position: 1,
            description: 'Test',
            hsHeading: '4403',
            volume: 100.5,
            netWeight: 200.3,
            supplementaryUnit: 'M3',
            supplementaryUnitQualifier: 'NET',
            speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
            producers: [new Producer('DE', 'geojson')],
        );

        $this->assertSame(100.5, $commodity->volume);
        $this->assertSame(200.3, $commodity->netWeight);
        $this->assertSame('M3', $commodity->supplementaryUnit);
        $this->assertSame('NET', $commodity->supplementaryUnitQualifier);
    }
}
