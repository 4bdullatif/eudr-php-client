<?php

declare(strict_types=1);

namespace Eudr\Requests;

use Eudr\Config\Config;
use Eudr\Exceptions\ValidationException;
use Eudr\Support\Xml;

final class EchoRequest extends AbstractRequest
{
    private function __construct(private string $query = 'hello')
    {
    }

    public static function make(string $query = 'hello'): static
    {
        return new self($query);
    }

    public function withQuery(string $query): static
    {
        $clone = clone $this;
        $clone->query = $query;
        return $clone;
    }

    public function validate(): void
    {
        if ($this->query === '') {
            throw new ValidationException('Echo query cannot be empty');
        }
    }

    public function getEndpoint(): string
    {
        return 'EudrEchoService';
    }

    public function getSoapAction(): string
    {
        return Xml::EUDR_ECHO_NS;
    }

    public function toSoapXml(Config $config): string
    {
        $builder = $this->createBuilder($config);

        $builder->addNamespace('echo', Xml::EUDR_ECHO_NS);

        $body = $builder->getBody();

        $echoRequest = $builder->createElement('echo:EudrEchoRequest');
        $body->appendChild($echoRequest);

        $queryEl = $builder->createElement('echo:query', $this->query);
        $echoRequest->appendChild($queryEl);

        return $builder->toString();
    }
}
