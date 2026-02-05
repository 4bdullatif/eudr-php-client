<?php

declare(strict_types=1);

namespace Eudr\Requests\V1;

use Eudr\Config\Config;
use Eudr\Exceptions\ValidationException;
use Eudr\Requests\AbstractRequest;
use Eudr\Support\Xml;

final class RetractDdsRequest extends AbstractRequest
{
    private ?string $ddsIdentifier = null;

    private function __construct()
    {
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

    public function validate(): void
    {
        if ($this->ddsIdentifier === null || $this->ddsIdentifier === '') {
            throw new ValidationException('DDS identifier is required for retract');
        }
    }

    public function getEndpoint(): string
    {
        return 'EUDRSubmissionServiceV1';
    }

    public function getSoapAction(): string
    {
        return Xml::EUDR_SUBMISSION_NS_V1 . '/retractDds';
    }

    public function toSoapXml(Config $config): string
    {
        $builder = $this->createBuilder($config);

        $builder->addNamespace('v1', Xml::EUDR_SUBMISSION_NS_V1);

        $body = $builder->getBody();

        $retractRequest = $builder->createElement('v1:RetractStatementRequest');
        $body->appendChild($retractRequest);

        $idEl = $builder->createElement('v1:ddsIdentifier', $this->ddsIdentifier ?? '');
        $retractRequest->appendChild($idEl);

        return $builder->toString();
    }
}
