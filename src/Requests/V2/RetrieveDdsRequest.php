<?php

declare(strict_types=1);

namespace Eudr\Requests\V2;

use Eudr\Config\Config;
use Eudr\Exceptions\ValidationException;
use Eudr\Requests\AbstractRequest;
use Eudr\Support\Xml;

/**
 * Retrieves DDS information by identifier(s) or by internal reference number.
 *
 * The two query modes are mutually exclusive:
 * - withIdentifier() / withIdentifiers(): query by one or more DDS UUIDs.
 * - withReference(): query by an internal reference number.
 *
 * Calling one mode automatically clears the other, so only the last-used mode
 * is active when the request is sent.
 */
final class RetrieveDdsRequest extends AbstractRequest
{
    /** @var list<string> */
    private array $identifiers = [];
    private ?string $reference = null;

    private function __construct()
    {
    }

    public static function make(): static
    {
        return new self();
    }

    public function withIdentifier(string $identifier): static
    {
        $clone = clone $this;
        $clone->identifiers = [$identifier];
        $clone->reference = null;
        return $clone;
    }

    /**
     * @param list<string> $identifiers Up to 100 UUIDs per call.
     */
    public function withIdentifiers(array $identifiers): static
    {
        $clone = clone $this;
        $clone->identifiers = $identifiers;
        $clone->reference = null;
        return $clone;
    }

    public function withReference(string $reference): static
    {
        $clone = clone $this;
        $clone->reference = $reference;
        $clone->identifiers = [];
        return $clone;
    }

    public function validate(): void
    {
        if ($this->identifiers === [] && $this->reference === null) {
            throw new ValidationException('Either identifier or reference is required');
        }

        if (\count($this->identifiers) > 100) {
            throw new ValidationException('Maximum 100 identifiers per request');
        }

        if ($this->reference !== null && (mb_strlen($this->reference) < 3 || mb_strlen($this->reference) > 50)) {
            throw new ValidationException('Reference must be between 3 and 50 characters');
        }
    }

    public function getEndpoint(): string
    {
        return 'EUDRRetrievalServiceV2';
    }

    public function getSoapAction(): string
    {
        if ($this->reference !== null) {
            return Xml::EUDR_RETRIEVAL_NS_V2 . '/getDdsInfoByInternalReferenceNumber';
        }

        return Xml::EUDR_RETRIEVAL_NS_V2 . '/getDdsInfo';
    }

    public function toSoapXml(Config $config): string
    {
        $builder = $this->createBuilder($config);

        $builder->addNamespace('v2', Xml::EUDR_RETRIEVAL_NS_V2);

        $body = $builder->getBody();

        if ($this->identifiers !== []) {
            $getRequest = $builder->createElement('v2:GetStatementInfoRequest');
            $body->appendChild($getRequest);

            foreach ($this->identifiers as $identifier) {
                $idEl = $builder->createElement('v2:identifier', $identifier);
                $getRequest->appendChild($idEl);
            }
        } elseif ($this->reference !== null) {
            $refRequest = $builder->createElement('v2:GetDdsInfoByInternalReferenceNumberRequest', $this->reference);
            $body->appendChild($refRequest);
        }

        return $builder->toString();
    }
}
