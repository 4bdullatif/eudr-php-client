<?php

declare(strict_types=1);

namespace Eudr\Requests\V1;

use Eudr\Config\Config;
use Eudr\Requests\AbstractSubmitDdsRequest;
use Eudr\Requests\V1\Concerns\BuildsV1StatementXml;
use Eudr\Support\Xml;

final class SubmitDdsRequest extends AbstractSubmitDdsRequest
{
    use BuildsV1StatementXml;

    private function __construct()
    {
        parent::__construct();
    }

    public static function make(): static
    {
        return new self();
    }

    public function getEndpoint(): string
    {
        return 'EUDRSubmissionServiceV1';
    }

    public function getSoapAction(): string
    {
        return Xml::EUDR_SUBMISSION_NS_V1 . '/submitDds';
    }

    public function toSoapXml(Config $config): string
    {
        $builder = $this->createBuilder($config);

        $builder->addNamespace('v1', Xml::EUDR_SUBMISSION_NS_V1);
        $builder->addNamespace('v11', Xml::EUDR_MODEL_NS_V1);

        $body = $builder->getBody();

        $submitRequest = $builder->createElement('v1:SubmitStatementRequest');
        $body->appendChild($submitRequest);

        if ($this->operatorType !== null) {
            $opTypeEl = $builder->createElement('v1:operatorType', $this->operatorType->value);
            $submitRequest->appendChild($opTypeEl);
        }

        $statement = $builder->createElement('v1:statement');
        $submitRequest->appendChild($statement);

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
