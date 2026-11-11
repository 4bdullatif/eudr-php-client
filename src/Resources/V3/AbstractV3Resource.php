<?php

declare(strict_types=1);

namespace Eudr\Resources\V3;

use Eudr\Exceptions\ApiException;
use Eudr\Exceptions\AuthenticationException;
use Eudr\Exceptions\HttpException;
use Eudr\Exceptions\XmlException;
use Eudr\Http\Connector;
use Eudr\Requests\V3\AbstractV3Request;
use Eudr\Resources\Resource;
use Eudr\Responses\ErrorResponse;

/**
 * Base class for V3 resources: adds an optional operator context (BodyIdentity header)
 * applied to every request that does not carry its own.
 */
abstract class AbstractV3Resource extends Resource
{
    final public function __construct(
        Connector $connector,
        private readonly ?string $operatorAccessIdentifier = null,
    ) {
        parent::__construct($connector);
    }

    /**
     * Return a copy of this resource that acts on behalf of the operator identified by the
     * given Web Service Access Identifier. Needed when the API user is linked to several operators.
     */
    public function forOperator(string $operatorAccessIdentifier): static
    {
        return new static($this->connector, $operatorAccessIdentifier);
    }

    public function getOperatorAccessIdentifier(): ?string
    {
        return $this->operatorAccessIdentifier;
    }

    /**
     * @template T of AbstractV3Request
     *
     * @param T $request
     *
     * @return T
     */
    protected function prepare(AbstractV3Request $request): AbstractV3Request
    {
        if ($this->operatorAccessIdentifier !== null && $request->getOperatorAccessIdentifier() === null) {
            return $request->withOperatorAccessIdentifier($this->operatorAccessIdentifier);
        }

        return $request;
    }

    /**
     * @throws ApiException SOAP fault, including faults returned with an HTTP error status.
     * @throws XmlException
     * @throws AuthenticationException
     * @throws HttpException HTTP error without a SOAP fault body.
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    protected function sendV3(AbstractV3Request $request): \SimpleXMLElement
    {
        try {
            return $this->sendAndParse($this->prepare($request));
        } catch (AuthenticationException $e) {
            throw $e;
        } catch (HttpException $e) {
            // SOAP 1.1 services return faults with HTTP 500. Surface them as ApiException so the
            // structured EUDR error details (e.g. EUDR_API_AMEND_DEADLINE_ELAPSED) are available.
            $fault = self::parseFault($e->responseBody);

            if ($fault === null) {
                throw $e;
            }

            throw new ApiException(
                'SOAP Fault: ' . $fault->faultString . ' (' . $fault->faultCode . ')',
                $fault,
                0,
                $e,
            );
        }
    }

    private static function parseFault(string $body): ?ErrorResponse
    {
        if ($body === '') {
            return null;
        }

        try {
            return ErrorResponse::fromXml($body);
        } catch (XmlException) {
            return null;
        }
    }
}
