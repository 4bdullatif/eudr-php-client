<?php

declare(strict_types=1);

namespace Eudr\Requests\Builders;

use Eudr\Data\Address;
use Eudr\Data\EconomicOperator;

/**
 * Fluent builder for constructing EconomicOperator value objects.
 */
final class OperatorBuilder
{
    private ?string $name = null;
    private ?Address $address = null;
    private ?string $email = null;
    private ?string $phone = null;
    /** @var list<array{identifierType: string, identifierValue: string}> */
    private array $referenceNumbers = [];

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function address(Address $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function email(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function phone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function addReferenceNumber(string $identifierType, string $identifierValue): self
    {
        $this->referenceNumbers[] = [
            'identifierType' => $identifierType,
            'identifierValue' => $identifierValue,
        ];
        return $this;
    }

    /**
     * @param list<array{identifierType: string, identifierValue: string}> $referenceNumbers
     */
    public function withReferenceNumbers(array $referenceNumbers): self
    {
        $this->referenceNumbers = $referenceNumbers;
        return $this;
    }

    public function build(): EconomicOperator
    {
        return new EconomicOperator(
            name: $this->name,
            address: $this->address,
            email: $this->email,
            phone: $this->phone,
            referenceNumbers: $this->referenceNumbers,
        );
    }
}
