<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Data;

use Eudr\Data\EconomicOperator;
use Eudr\Exceptions\ValidationException;
use Eudr\Tests\TestCase;

final class EconomicOperatorTest extends TestCase
{
    public function testValidatePassesWithReferenceNumber(): void
    {
        $operator = new EconomicOperator(
            referenceNumbers: [
                ['identifierType' => 'EORI', 'identifierValue' => 'DE123456789'],
            ],
        );

        $operator->validate();
        $this->addToAssertionCount(1);
    }

    public function testValidateFailsWhenReferenceNumbersEmpty(): void
    {
        $operator = new EconomicOperator();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('at least one reference number');
        $operator->validate();
    }

    public function testValidatePassesWithMultipleReferenceNumbers(): void
    {
        $operator = new EconomicOperator(
            referenceNumbers: [
                ['identifierType' => 'EORI', 'identifierValue' => 'DE123456789'],
                ['identifierType' => 'VAT', 'identifierValue' => 'DE987654321'],
            ],
        );

        $operator->validate();
        $this->addToAssertionCount(1);
    }

    public function testValidateFailsWithEmptyIdentifierType(): void
    {
        $operator = new EconomicOperator(
            referenceNumbers: [
                ['identifierType' => '', 'identifierValue' => 'DE123456789'],
            ],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('identifierType');
        $operator->validate();
    }

    public function testValidateFailsWithEmptyIdentifierValue(): void
    {
        $operator = new EconomicOperator(
            referenceNumbers: [
                ['identifierType' => 'EORI', 'identifierValue' => ''],
            ],
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('identifierValue');
        $operator->validate();
    }
}
