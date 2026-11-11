<?php

declare(strict_types=1);

namespace Eudr;

use Eudr\Config\Config;
use Eudr\Http\Connector;
use Eudr\Http\Middleware\Middleware;
use Eudr\Requests\EchoRequest;
use Eudr\Resources\DdsResource;
use Eudr\Resources\V1\DdsResource as DdsResourceV1;
use Eudr\Resources\V3\DdsResource as DdsResourceV3;
use Eudr\Resources\V3\SdResource as SdResourceV3;
use Eudr\Responses\EchoResponse;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class EudrClient
{
    private readonly Connector $connector;
    private ?DdsResource $ddsResource = null;
    private ?DdsResourceV1 $ddsResourceV1 = null;
    private ?DdsResourceV3 $ddsResourceV3 = null;
    private ?SdResourceV3 $sdResourceV3 = null;

    /**
     * @param list<Middleware> $middleware
     */
    public function __construct(
        Config $config,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        array $middleware = [],
    ) {
        $this->connector = new Connector(
            config: $config,
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
            middleware: $middleware,
        );
    }

    public function dds(): DdsResource
    {
        if ($this->ddsResource === null) {
            $this->ddsResource = new DdsResource($this->connector);
        }

        return $this->ddsResource;
    }

    public function ddsV1(): DdsResourceV1
    {
        if ($this->ddsResourceV1 === null) {
            $this->ddsResourceV1 = new DdsResourceV1($this->connector);
        }

        return $this->ddsResourceV1;
    }

    /**
     * Due Diligence Statement operations of the V3 API (EUDRDueDiligenceStatementServiceV3).
     */
    public function ddsV3(): DdsResourceV3
    {
        if ($this->ddsResourceV3 === null) {
            $this->ddsResourceV3 = new DdsResourceV3($this->connector);
        }

        return $this->ddsResourceV3;
    }

    /**
     * Simplified Declaration operations of the V3 API (EUDRSimplifiedDeclarationServiceV3).
     */
    public function sdV3(): SdResourceV3
    {
        if ($this->sdResourceV3 === null) {
            $this->sdResourceV3 = new SdResourceV3($this->connector);
        }

        return $this->sdResourceV3;
    }

    /**
     * Test connectivity and authentication against the EUDR Echo service.
     * Available in acceptance/testing environments only.
     *
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function echo(string $query = 'hello'): EchoResponse
    {
        $request = EchoRequest::make($query);

        return EchoResponse::fromSoapBody($this->connector->sendAndParse($request));
    }

    public function getConnector(): Connector
    {
        return $this->connector;
    }
}
