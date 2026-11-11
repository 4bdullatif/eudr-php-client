<?php

declare(strict_types=1);

namespace Eudr\Requests\V3;

use Eudr\Config\Config;
use Eudr\Enums\V3\SdOperatorRole;
use Eudr\Exceptions\ValidationException;
use Eudr\Support\Xml;

/**
 * submitSd — submit a new Simplified Declaration (V3).
 *
 * Grouping accepts Simplified Declaration reference numbers only.
 */
final class SubmitSdRequest extends AbstractSdStatementRequest
{
    private ?SdOperatorRole $operatorRole = null;

    private function __construct()
    {
        parent::__construct();
    }

    public static function make(): static
    {
        return new self();
    }

    public function withOperatorRole(SdOperatorRole $role): static
    {
        $clone = clone $this;
        $clone->operatorRole = $role;
        return $clone;
    }

    public function getSoapAction(): string
    {
        return Xml::EUDR_SD_NS_V3 . '/submitSd';
    }

    public function validate(): void
    {
        if ($this->operatorRole === null) {
            throw new ValidationException('Operator role is required');
        }

        parent::validate();

        if ($this->operatorRole->requiresRepresentedOperator() && $this->representedOperator === null) {
            throw new ValidationException(
                "Represented operator is required when the operator role is {$this->operatorRole->value}",
            );
        }
    }

    public function toSoapXml(Config $config): string
    {
        $builder = $this->createSdBuilder($config);

        $request = $builder->createElement('sd:SubmitSdRequest');
        $builder->getBody()->appendChild($request);

        self::appendText($builder, $request, 'sd:operatorRole', $this->operatorRole->value ?? '');
        $this->appendStatement($builder, $request, true);

        return $builder->toString();
    }
}
