<?php

declare(strict_types=1);

namespace Eudr\Resources\V3;

use Eudr\Requests\V3\GetSdByInternalReferenceRequest;
use Eudr\Requests\V3\GetSdRequest;
use Eudr\Requests\V3\SubmitSdRequest;
use Eudr\Requests\V3\UpdateSdRequest;
use Eudr\Requests\V3\WithdrawSdRequest;
use Eudr\Responses\V3\ModificationResponse;
use Eudr\Responses\V3\OverviewResponse;
use Eudr\Responses\V3\SubmitSdResponse;

/**
 * Simplified Declaration operations of the V3 API (EUDRSimplifiedDeclarationServiceV3),
 * for micro or small primary operators, their authorised representatives and Member States.
 *
 * getSdByIdentifiers is not exposed: it has been discontinued as an API operation and
 * always returns a NotFoundException.
 */
final class SdResource extends AbstractV3Resource
{
    /**
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function submit(SubmitSdRequest $request): SubmitSdResponse
    {
        return SubmitSdResponse::fromSoapBody($this->sendV3($request));
    }

    /**
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function update(UpdateSdRequest $request): ModificationResponse
    {
        return ModificationResponse::fromSoapBody($this->sendV3($request));
    }

    /**
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function withdraw(WithdrawSdRequest $request): ModificationResponse
    {
        return ModificationResponse::fromSoapBody($this->sendV3($request));
    }

    /**
     * @return list<OverviewResponse>
     *
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function get(GetSdRequest $request): array
    {
        return OverviewResponse::allFromSoapBody($this->sendV3($request));
    }

    /**
     * @return list<OverviewResponse>
     *
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getByInternalReference(GetSdByInternalReferenceRequest $request): array
    {
        return OverviewResponse::allFromSoapBody($this->sendV3($request));
    }

    /**
     * Retrieve the overview of a single Simplified Declaration, optionally for a specific version.
     *
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function retrieve(string $uuid, ?int $version = null): OverviewResponse
    {
        return OverviewResponse::fromSoapBody($this->sendV3(GetSdRequest::make()->addUuid($uuid, $version)));
    }

    /**
     * Retrieve the overviews of up to 100 Simplified Declarations by UUID.
     *
     * @param list<string> $uuids
     * @return list<OverviewResponse>
     *
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function retrieveMany(array $uuids): array
    {
        return $this->get(GetSdRequest::make()->withUuids($uuids));
    }

    /**
     * Retrieve the first Simplified Declaration overview matching an internal reference number.
     *
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function retrieveByReference(string $internalReference): OverviewResponse
    {
        return OverviewResponse::fromSoapBody(
            $this->sendV3(GetSdByInternalReferenceRequest::make()->withInternalReference($internalReference)),
        );
    }

    /**
     * Retrieve all Simplified Declaration overviews matching an internal reference number.
     *
     * @return list<OverviewResponse>
     *
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function retrieveAllByReference(string $internalReference): array
    {
        return $this->getByInternalReference(
            GetSdByInternalReferenceRequest::make()->withInternalReference($internalReference),
        );
    }
}
