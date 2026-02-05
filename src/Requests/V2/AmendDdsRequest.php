<?php

declare(strict_types=1);

namespace Eudr\Requests\V2;

use Eudr\Config\Config;
use Eudr\Exceptions\ValidationException;
use Eudr\Requests\AbstractSubmitDdsRequest;
use Eudr\Requests\V2\Concerns\BuildsV2StatementXml;
use Eudr\Support\Xml;

final class AmendDdsRequest extends AbstractSubmitDdsRequest
{
    use BuildsV2StatementXml;

    private ?string $ddsIdentifier = null;

    private function __construct()
    {
        parent::__construct();
    }

    public static function make(): static
    {
        return new self();
    }

    public function withDdsIdentifier(string $identifier): static
    {
        $clone = clone $this;
        $clone->ddsIdentifier = $identifier;
        return $clone;
    }

    public function getEndpoint(): string
    {
        return 'EUDRSubmissionServiceV2';
    }

    public function getSoapAction(): string
    {
        return Xml::EUDR_SUBMISSION_NS_V2 . '/amendDds';
    }

    public function validate(): void
    {
        if ($this->ddsIdentifier === null || $this->ddsIdentifier === '') {
            throw new ValidationException('DDS identifier is required for amend');
        }

        parent::validate();
    }

    public function toSoapXml(Config $config): string
    {
        $builder = $this->createBuilder($config);

        $builder->addNamespace('v2', Xml::EUDR_SUBMISSION_NS_V2);
        $builder->addNamespace('v21', Xml::EUDR_MODEL_NS_V2);

        $body = $builder->getBody();

        $amendRequest = $builder->createElement('v2:AmendStatementRequest');
        $body->appendChild($amendRequest);

        $idEl = $builder->createElement('v2:ddsIdentifier', $this->ddsIdentifier ?? '');
        $amendRequest->appendChild($idEl);

        if ($this->operatorType !== null) {
            $opTypeEl = $builder->createElement('v2:operatorType', $this->operatorType->value);
            $amendRequest->appendChild($opTypeEl);
        }

        $statement = $builder->createElement('v2:statement');
        $amendRequest->appendChild($statement);

        $this->buildStatementChildren(
            $builder,
            $statement,
            $this->internalReference,
            $this->activityType,
            $this->operator,
            $this->countryOfActivity,
            $this->borderCrossCountry,
            $this->comment,
            $this->commodities,
            $this->geoLocationConfidential,
            $this->associatedStatements,
        );

        return $builder->toString();
    }
}
