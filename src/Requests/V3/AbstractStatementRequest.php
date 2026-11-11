<?php

declare(strict_types=1);

namespace Eudr\Requests\V3;

use Eudr\Data\V3\RepresentedOperator;
use Eudr\Enums\ActivityType;
use Eudr\Exceptions\ValidationException;
use Eudr\Requests\V3\Concerns\BuildsV3Xml;
use Eudr\Support\V3\Rules;
use Eudr\Support\XmlBuilder;

/**
 * Shared statement fields for V3 DDS (DueDiligenceStatementBaseType) and
 * SD (SimplifiedDeclarationBaseType) submit/amend/update requests.
 */
abstract class AbstractStatementRequest extends AbstractV3Request
{
    use BuildsV3Xml;

    public const MAX_COMMODITIES = 200;
    public const MAX_GROUPED_DECLARATIONS = 2000;

    protected ?string $internalReference = null;
    protected ?ActivityType $activityType = null;
    protected ?RepresentedOperator $representedOperator = null;
    protected ?string $countryOfActivity = null;
    protected ?string $borderCrossCountry = null;
    protected ?string $comment = null;
    protected bool $geoLocationConfidential = false;
    /** @var list<string> */
    protected array $groupedDeclarations = [];

    protected function __construct()
    {
    }

    /**
     * Namespace prefix of the statement elements ("dds" or "sd").
     */
    abstract protected function statementPrefix(): string;

    /**
     * Maximum length of the operator internal reference number.
     */
    abstract protected function internalReferenceMaxLength(): int;

    /**
     * @return int Number of commodities on the statement.
     */
    abstract protected function commodityCount(): int;

    /**
     * @throws ValidationException
     */
    abstract protected function validateCommodities(): void;

    abstract protected function appendCommodities(XmlBuilder $builder, \DOMElement $statement): void;

    public function withInternalReference(string $reference): static
    {
        $clone = clone $this;
        $clone->internalReference = $reference;
        return $clone;
    }

    /**
     * Allowed values: DOMESTIC, IMPORT, EXPORT (TRADE does not exist in V3).
     */
    public function withActivityType(ActivityType $type): static
    {
        $clone = clone $this;
        $clone->activityType = $type;
        return $clone;
    }

    public function withRepresentedOperator(RepresentedOperator $operator): static
    {
        $clone = clone $this;
        $clone->representedOperator = $operator;
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

    public function withGeoLocationConfidential(bool $confidential = true): static
    {
        $clone = clone $this;
        $clone->geoLocationConfidential = $confidential;
        return $clone;
    }

    /**
     * Group a previously submitted declaration (by its reference number) under this one.
     * Only allowed on initial submission.
     */
    public function addGroupedDeclaration(string $referenceNumber): static
    {
        $clone = clone $this;
        $clone->groupedDeclarations = [...$this->groupedDeclarations, $referenceNumber];
        return $clone;
    }

    /**
     * @param list<string> $referenceNumbers
     */
    public function withGroupedDeclarations(array $referenceNumbers): static
    {
        $clone = clone $this;
        $clone->groupedDeclarations = $referenceNumbers;
        return $clone;
    }

    public function validate(): void
    {
        parent::validate();

        if ($this->activityType === null) {
            throw new ValidationException('Activity type is required');
        }

        if ($this->activityType === ActivityType::TRADE) {
            throw new ValidationException('Activity type TRADE is not supported by the V3 API (use DOMESTIC, IMPORT or EXPORT)');
        }

        if ($this->internalReference !== null) {
            Rules::length($this->internalReference, 0, $this->internalReferenceMaxLength(), 'Internal reference number');
        }

        $this->representedOperator?->validate();

        if ($this->countryOfActivity !== null) {
            Rules::europeanCountry($this->countryOfActivity, 'Country of activity');
        }

        if ($this->borderCrossCountry !== null) {
            Rules::europeanCountry($this->borderCrossCountry, 'Border cross country');
        }

        if ($this->comment !== null) {
            Rules::length($this->comment, 0, 2000, 'Comment');
        }

        $count = $this->commodityCount();
        if ($count === 0 || $count > self::MAX_COMMODITIES) {
            throw new ValidationException('Must have between 1 and ' . self::MAX_COMMODITIES . ' commodities');
        }

        $this->validateCommodities();

        if (\count($this->groupedDeclarations) > self::MAX_GROUPED_DECLARATIONS) {
            throw new ValidationException('Must not exceed ' . self::MAX_GROUPED_DECLARATIONS . ' grouped declarations');
        }

        foreach ($this->groupedDeclarations as $index => $referenceNumber) {
            Rules::nonBlank($referenceNumber, "Grouped declaration #{$index} reference number");
            Rules::length($referenceNumber, 0, 14, "Grouped declaration #{$index} reference number");
        }
    }

    /**
     * Append the statement element (in the service namespace) with all children in schema order.
     */
    protected function appendStatement(XmlBuilder $builder, \DOMElement $parent, bool $alwaysEmitInternalReference): void
    {
        $p = $this->statementPrefix();

        $statement = $builder->createElement("{$p}:statement");
        $parent->appendChild($statement);

        if ($this->internalReference !== null || $alwaysEmitInternalReference) {
            self::appendText($builder, $statement, "{$p}:internalReferenceNumber", $this->internalReference ?? '');
        }

        self::appendText($builder, $statement, "{$p}:activityType", $this->activityType->value ?? '');

        if ($this->representedOperator !== null) {
            self::appendRepresentedOperator($builder, $statement, "{$p}:representedOperator", $this->representedOperator);
        }

        if ($this->countryOfActivity !== null) {
            self::appendText($builder, $statement, "{$p}:countryOfActivity", $this->countryOfActivity);
        }

        if ($this->borderCrossCountry !== null) {
            self::appendText($builder, $statement, "{$p}:borderCrossCountry", $this->borderCrossCountry);
        }

        if ($this->comment !== null) {
            self::appendText($builder, $statement, "{$p}:comment", $this->comment);
        }

        $this->appendCommodities($builder, $statement);

        self::appendText($builder, $statement, "{$p}:geoLocationConfidential", $this->geoLocationConfidential ? 'true' : 'false');

        foreach ($this->groupedDeclarations as $referenceNumber) {
            $groupedEl = $builder->createElement("{$p}:groupedDeclarations");
            $statement->appendChild($groupedEl);
            self::appendText($builder, $groupedEl, 'eudrCommon:groupedDeclaration', $referenceNumber);
        }
    }

    /**
     * @throws ValidationException
     */
    protected function assertNoGroupedDeclarations(string $operation): void
    {
        if ($this->groupedDeclarations !== []) {
            throw new ValidationException(
                "Grouped declarations cannot be added, modified or removed on {$operation}; grouping can only be set on initial submission",
            );
        }
    }
}
