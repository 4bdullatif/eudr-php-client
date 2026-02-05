<?php

declare(strict_types=1);

namespace Eudr\Requests\V2;

use Eudr\Config\Config;
use Eudr\Requests\AbstractSubmitDdsRequest;
use Eudr\Requests\V2\Concerns\BuildsV2StatementXml;
use Eudr\Support\Xml;

final class SubmitDdsRequest extends AbstractSubmitDdsRequest
{
    use BuildsV2StatementXml;

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
        return 'EUDRSubmissionServiceV2';
    }

    public function getSoapAction(): string
    {
        return Xml::EUDR_SUBMISSION_NS_V2 . '/submitDds';
    }

    public function toSoapXml(Config $config): string
    {
        $builder = $this->createBuilder($config);

        $builder->addNamespace('v2', Xml::EUDR_SUBMISSION_NS_V2);
        $builder->addNamespace('v21', Xml::EUDR_MODEL_NS_V2);

        $body = $builder->getBody();

        // v2:SubmitStatementRequest
        $submitRequest = $builder->createElement('v2:SubmitStatementRequest');
        $body->appendChild($submitRequest);

        // v2:operatorType
        if ($this->operatorType !== null) {
            $opTypeEl = $builder->createElement('v2:operatorType', $this->operatorType->value);
            $submitRequest->appendChild($opTypeEl);
        }

        // v2:statement
        $statement = $builder->createElement('v2:statement');
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
