<?php

declare(strict_types=1);

namespace Eudr\Requests;

use Eudr\Data\Commodity;
use Eudr\Data\EconomicOperator;
use Eudr\Enums\ActivityType;
use Eudr\Enums\OperatorType;
use Eudr\Exceptions\ValidationException;

/**
 * Base class for DDS submit and amend request builders, providing shared fields and fluent setters.
 */
abstract class AbstractSubmitDdsRequest extends AbstractRequest
{
    protected ?OperatorType $operatorType = null;
    protected ?string $internalReference = null;
    protected ?ActivityType $activityType = null;
    /** @var list<Commodity> */
    protected array $commodities = [];
    protected bool $geoLocationConfidential = false;
    protected ?EconomicOperator $operator = null;
    protected ?string $countryOfActivity = null;
    protected ?string $borderCrossCountry = null;
    protected ?string $comment = null;
    /** @var list<array{referenceNumber: string, verificationNumber: string}> */
    protected array $associatedStatements = [];

    protected function __construct()
    {
    }

    public function withOperatorType(OperatorType $type): static
    {
        $clone = clone $this;
        $clone->operatorType = $type;
        return $clone;
    }

    public function withInternalReference(string $reference): static
    {
        $clone = clone $this;
        $clone->internalReference = $reference;
        return $clone;
    }

    public function withActivityType(ActivityType $type): static
    {
        $clone = clone $this;
        $clone->activityType = $type;
        return $clone;
    }

    public function addCommodity(Commodity $commodity): static
    {
        $clone = clone $this;
        $clone->commodities = [...$this->commodities, $commodity];
        return $clone;
    }

    /**
     * @param list<Commodity> $commodities
     */
    public function withCommodities(array $commodities): static
    {
        $clone = clone $this;
        $clone->commodities = $commodities;
        return $clone;
    }

    public function withGeoLocationConfidential(bool $confidential = true): static
    {
        $clone = clone $this;
        $clone->geoLocationConfidential = $confidential;
        return $clone;
    }

    public function withOperator(EconomicOperator $operator): static
    {
        $clone = clone $this;
        $clone->operator = $operator;
        return $clone;
    }

    public function withCountryOfActivity(string $country): static
    {
        $clone = clone $this;
        $clone->countryOfActivity = $country;
        return $clone;
    }

    public function withBorderCrossCountry(string $country): static
    {
        $clone = clone $this;
        $clone->borderCrossCountry = $country;
        return $clone;
    }

    public function withComment(string $comment): static
    {
        $clone = clone $this;
        $clone->comment = $comment;
        return $clone;
    }

    public function addAssociatedStatement(string $referenceNumber, string $verificationNumber): static
    {
        $clone = clone $this;
        $clone->associatedStatements = [
            ...$this->associatedStatements,
            ['referenceNumber' => $referenceNumber, 'verificationNumber' => $verificationNumber],
        ];
        return $clone;
    }

    /**
     * @param list<array{referenceNumber: string, verificationNumber: string}> $statements
     */
    public function withAssociatedStatements(array $statements): static
    {
        $clone = clone $this;
        $clone->associatedStatements = $statements;
        return $clone;
    }

    public function validate(): void
    {
        if ($this->operatorType === null) {
            throw new ValidationException('Operator type is required');
        }

        if ($this->activityType === null) {
            throw new ValidationException('Activity type is required');
        }

        if ($this->internalReference !== null && mb_strlen($this->internalReference) > 50) {
            throw new ValidationException('Internal reference number must not exceed 50 characters');
        }

        if ($this->comment !== null && mb_strlen($this->comment) > 2000) {
            throw new ValidationException('Comment must not exceed 2000 characters');
        }

        if ($this->countryOfActivity !== null && !preg_match('/^[A-Z]{2}$/', $this->countryOfActivity)) {
            throw new ValidationException('Country of activity must be a valid ISO 3166-1 alpha-2 code (e.g., "DE", "FR")');
        }

        if ($this->borderCrossCountry !== null && !preg_match('/^[A-Z]{2}$/', $this->borderCrossCountry)) {
            throw new ValidationException('Border cross country must be a valid ISO 3166-1 alpha-2 code (e.g., "DE", "FR")');
        }

        if ($this->commodities === [] || \count($this->commodities) > 200) {
            throw new ValidationException('Must have between 1 and 200 commodities');
        }

        if (\count($this->associatedStatements) > 2000) {
            throw new ValidationException('Must not exceed 2000 associated statements');
        }

        foreach ($this->commodities as $commodity) {
            $commodity->validate();
        }

        if ($this->operator !== null) {
            $this->operator->validate();
        }
    }
}
