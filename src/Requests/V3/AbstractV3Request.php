<?php

declare(strict_types=1);

namespace Eudr\Requests\V3;

use Eudr\Config\Config;
use Eudr\Exceptions\ValidationException;
use Eudr\Requests\AbstractRequest;
use Eudr\Support\Xml;
use Eudr\Support\XmlBuilder;

/**
 * Base class for V3 requests.
 *
 * Adds support for the optional BodyIdentity SOAP header, which selects the operator context
 * when the API user is linked to several operators (Web Service Access Identifier).
 */
abstract class AbstractV3Request extends AbstractRequest
{
    private ?string $operatorAccessIdentifier = null;

    /**
     * Act on behalf of the operator identified by this Web Service Access Identifier
     * (sent as BodyIdentity/OperatorAccessIdentifier). Required when the API user is
     * linked to more than one operator.
     */
    public function withOperatorAccessIdentifier(string $identifier): static
    {
        $clone = clone $this;
        $clone->operatorAccessIdentifier = $identifier;
        return $clone;
    }

    public function getOperatorAccessIdentifier(): ?string
    {
        return $this->operatorAccessIdentifier;
    }

    public function validate(): void
    {
        if ($this->operatorAccessIdentifier !== null) {
            $identifier = trim($this->operatorAccessIdentifier);

            if ($identifier === '') {
                throw new ValidationException('Operator access identifier must not be empty');
            }

            if (mb_strlen($identifier) > 16) {
                throw new ValidationException('Operator access identifier must not exceed 16 characters');
            }
        }
    }

    protected function createBuilder(Config $config): XmlBuilder
    {
        $builder = parent::createBuilder($config);

        if ($this->operatorAccessIdentifier !== null) {
            $doc = $builder->getDocument();

            $bodyIdentity = $doc->createElementNS(Xml::TRACESNT_BODY_NS_V3, 'body:BodyIdentity');
            $builder->getHeader()->appendChild($bodyIdentity);

            // The child element must be unqualified (no namespace).
            $identifierEl = $doc->createElementNS(null, 'OperatorAccessIdentifier');
            $identifierEl->appendChild($doc->createTextNode(trim($this->operatorAccessIdentifier)));
            $bodyIdentity->appendChild($identifierEl);
        }

        return $builder;
    }
}
