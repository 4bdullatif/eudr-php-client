<?php

declare(strict_types=1);

namespace Eudr\Requests\V4;

use Eudr\Config\Config;
use Eudr\Enums\V4\OperatorRole;
use Eudr\Exceptions\ValidationException;
use Eudr\Support\Xml;

/**
 * submitDds — submit a new Due Diligence Statement (V4).
 */
final class SubmitDdsRequest extends AbstractDdsStatementRequest
{
    private ?OperatorRole $operatorRole = null;

    private function __construct()
    {
        parent::__construct();
    }

    public static function make(): static
    {
        return new self();
    }

    public function withOperatorRole(OperatorRole $role): static
    {
        $clone = clone $this;
        $clone->operatorRole = $role;
        return $clone;
    }

    public function getSoapAction(): string
    {
        return Xml::EUDR_DDS_NS_V4 . '/submitDds';
    }

    public function validate(): void
    {
        if ($this->operatorRole === null) {
            throw new ValidationException('Operator role is required');
        }

        parent::validate();

        if ($this->operatorRole === OperatorRole::REPRESENTATIVE_OPERATOR && $this->representedOperator === null) {
            throw new ValidationException('Represented operator is required when the operator role is REPRESENTATIVE_OPERATOR');
        }

        if ($this->groupedDeclarations === []) {
            foreach ($this->commodities as $index => $commodity) {
                if ($commodity->producers === []) {
                    throw new ValidationException(
                        "Commodity #{$index} must have at least one producer unless the statement groups other declarations",
                    );
                }
            }
        }
    }

    public function toSoapXml(Config $config): string
    {
        $builder = $this->createDdsBuilder($config);

        $request = $builder->createElement('dds:SubmitDdsRequest');
        $builder->getBody()->appendChild($request);

        self::appendText($builder, $request, 'dds:operatorRole', $this->operatorRole->value ?? '');
        $this->appendStatement($builder, $request, false);

        return $builder->toString();
    }
}
