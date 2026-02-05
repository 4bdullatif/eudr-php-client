<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Data;

use Eudr\Data\Commodity;
use Eudr\Data\Producer;
use Eudr\Data\SpeciesInfo;
use Eudr\Requests\Builders\CommodityBuilder;
use Eudr\Tests\TestCase;

final class CommodityBuilderTest extends TestCase
{
    public function testNumberOfUnitsSetsTheField(): void
    {
        $builder = Commodity::make();
        $this->assertInstanceOf(CommodityBuilder::class, $builder);

        $commodity = $builder
            ->position(1)
            ->description('Test')
            ->hsHeading('4403')
            ->numberOfUnits(42)
            ->addProducer(new Producer('DE', 'geojson'))
            ->build();

        $this->assertSame(42, $commodity->numberOfUnits);
    }

    public function testBuildIncludesNumberOfUnitsInCommodity(): void
    {
        $commodity = Commodity::make()
            ->position(1)
            ->description('Wood planks')
            ->hsHeading('4407')
            ->numberOfUnits(100)
            ->netWeight(500.0)
            ->volume(10.5)
            ->addProducer(new Producer('FR', 'geojsondata'))
            ->build();

        $this->assertSame(100, $commodity->numberOfUnits);
        $this->assertSame(500.0, $commodity->netWeight);
        $this->assertSame(10.5, $commodity->volume);
    }

    public function testBuildWithoutNumberOfUnitsDefaultsToNull(): void
    {
        $commodity = Commodity::make()
            ->position(1)
            ->description('Test')
            ->hsHeading('4403')
            ->addProducer(new Producer('DE', 'geojson'))
            ->build();

        $this->assertNull($commodity->numberOfUnits);
    }

    public function testBuilderSetsAllFields(): void
    {
        $producer = new Producer('DE', 'geojson', 'Producer Name');
        $speciesInfo = new SpeciesInfo('Quercus robur', 'European Oak');

        $commodity = Commodity::make()
            ->position(3)
            ->description('Oak lumber')
            ->hsHeading('44071100')
            ->volume(50.5)
            ->netWeight(1200.0)
            ->numberOfUnits(25)
            ->supplementaryUnit('M3')
            ->supplementaryUnitQualifier('NET')
            ->percentageEstimationOrDeviation(2.5)
            ->addProducer($producer)
            ->addSpeciesInfo($speciesInfo)
            ->build();

        $this->assertSame(3, $commodity->position);
        $this->assertSame('Oak lumber', $commodity->description);
        $this->assertSame('44071100', $commodity->hsHeading);
        $this->assertSame(50.5, $commodity->volume);
        $this->assertSame(1200.0, $commodity->netWeight);
        $this->assertSame(25, $commodity->numberOfUnits);
        $this->assertSame('M3', $commodity->supplementaryUnit);
        $this->assertSame('NET', $commodity->supplementaryUnitQualifier);
        $this->assertSame(2.5, $commodity->percentageEstimationOrDeviation);
        $this->assertCount(1, $commodity->producers);
        $this->assertCount(1, $commodity->speciesInfo);
    }

    public function testWithProducersReplacesAll(): void
    {
        $producer1 = new Producer('DE', 'geojson1');
        $producer2 = new Producer('FR', 'geojson2');

        $commodity = Commodity::make()
            ->position(1)
            ->description('Test')
            ->hsHeading('4403')
            ->addProducer($producer1)
            ->withProducers([$producer2])
            ->build();

        $this->assertCount(1, $commodity->producers);
        $this->assertSame('FR', $commodity->producers[0]->country);
    }

    public function testWithSpeciesInfoReplacesAll(): void
    {
        $species1 = new SpeciesInfo('Quercus robur', 'Oak');
        $species2 = new SpeciesInfo('Fagus sylvatica', 'Beech');

        $commodity = Commodity::make()
            ->position(1)
            ->description('Test')
            ->hsHeading('4403')
            ->addSpeciesInfo($species1)
            ->withSpeciesInfo([$species2])
            ->addProducer(new Producer('DE', 'geojson'))
            ->build();

        $this->assertCount(1, $commodity->speciesInfo);
        $this->assertSame('Fagus sylvatica', $commodity->speciesInfo[0]->scientificName);
    }
}
