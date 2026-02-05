<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Data;

use Eudr\Data\SpeciesInfo;
use Eudr\Exceptions\ValidationException;
use Eudr\Tests\TestCase;

final class SpeciesInfoTest extends TestCase
{
    public function testValidateFailsWhenBothNamesAreNull(): void
    {
        $species = new SpeciesInfo();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('SpeciesInfo must have at least a scientific name or a common name');
        $species->validate();
    }

    public function testValidatePassesWithScientificName(): void
    {
        $species = new SpeciesInfo(scientificName: 'Quercus robur');

        $species->validate();
        $this->addToAssertionCount(1);
    }

    public function testValidatePassesWithCommonName(): void
    {
        $species = new SpeciesInfo(commonName: 'Oak');

        $species->validate();
        $this->addToAssertionCount(1);
    }

    public function testValidatePassesWithBothNames(): void
    {
        $species = new SpeciesInfo(scientificName: 'Quercus robur', commonName: 'Oak');

        $species->validate();
        $this->addToAssertionCount(1);
    }
}
