<?php

declare(strict_types=1);

namespace Eudr\Requests\V1;

use Eudr\Config\Config;
use Eudr\Exceptions\ValidationException;
use Eudr\Requests\AbstractRequest;
use Eudr\Support\Xml;

final class GetStatementByIdentifiersRequest extends AbstractRequest
{
    private ?string $referenceNumber = null;
    private ?string $verificationNumber = null;

    private function __construct()
    {
    }

    public static function make(): static
    {
        return new self();
    }

    public function withReferenceNumber(string $referenceNumber): static
    {
        $clone = clone $this;
        $clone->referenceNumber = $referenceNumber;
        return $clone;
    }

    public function withVerificationNumber(string $verificationNumber): static
    {
        $clone = clone $this;
        $clone->verificationNumber = $verificationNumber;
        return $clone;
    }

    public function validate(): void
    {
        if ($this->referenceNumber === null || $this->referenceNumber === '') {
            throw new ValidationException('Reference number is required');
        }

        if ($this->verificationNumber === null || $this->verificationNumber === '') {
            throw new ValidationException('Verification number is required');
        }
    }

    public function getEndpoint(): string
    {
        return 'EUDRRetrievalServiceV1';
    }

    public function getSoapAction(): string
    {
        return Xml::EUDR_RETRIEVAL_NS_V1 . '/getStatementByIdentifiers';
    }

    public function toSoapXml(Config $config): string
    {
        $builder = $this->createBuilder($config);

        $builder->addNamespace('v1', Xml::EUDR_RETRIEVAL_NS_V1);

        $body = $builder->getBody();

        $request = $builder->createElement('v1:GetStatementByIdentifiersRequest');
        $body->appendChild($request);

        $refEl = $builder->createElement('v1:referenceNumber', $this->referenceNumber ?? '');
        $request->appendChild($refEl);

        $verEl = $builder->createElement('v1:verificationNumber', $this->verificationNumber ?? '');
        $request->appendChild($verEl);

        return $builder->toString();
    }
}
