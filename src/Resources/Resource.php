<?php

declare(strict_types=1);

namespace Eudr\Resources;

use Eudr\Exceptions\ApiException;
use Eudr\Http\Connector;
use Eudr\Requests\Request;

abstract class Resource
{
    public function __construct(
        protected readonly Connector $connector,
    ) {
    }

    /**
     * Send a request, parse the SOAP envelope once, check for faults,
     * and return the pre-parsed SOAP Body element.
     *
     * @throws ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    protected function sendAndParse(Request $request): \SimpleXMLElement
    {
        return $this->connector->sendAndParse($request);
    }
}
