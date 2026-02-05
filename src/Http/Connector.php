<?php

declare(strict_types=1);

namespace Eudr\Http;

use Eudr\Config\Config;
use Eudr\Exceptions\ApiException;
use Eudr\Exceptions\AuthenticationException;
use Eudr\Exceptions\ConfigurationException;
use Eudr\Exceptions\HttpException;
use Eudr\Http\Middleware\LoggingMiddleware;
use Eudr\Http\Middleware\Middleware;
use Eudr\Http\Middleware\MiddlewarePipeline;
use Eudr\Requests\Request;
use Eudr\Support\SoapEnvelope;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Connector
{
    private readonly ClientInterface $httpClient;
    private readonly RequestFactoryInterface $requestFactory;
    private readonly StreamFactoryInterface $streamFactory;
    /** @var list<Middleware> */
    private readonly array $middleware;
    private readonly MiddlewarePipeline $pipeline;

    /**
     * @param list<Middleware> $middleware
     */
    public function __construct(
        private readonly Config $config,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        array $middleware = [],
    ) {
        $this->httpClient = $httpClient ?? self::discoverHttpClient($config->timeout);
        $this->requestFactory = $requestFactory ?? self::discoverRequestFactory();
        $this->streamFactory = $streamFactory ?? self::discoverStreamFactory();
        $this->middleware = $middleware;
        $this->pipeline = $this->buildPipeline();
    }

    /**
     * @throws AuthenticationException
     * @throws HttpException
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function send(Request $request): ResponseInterface
    {
        // Validate request before building XML
        if ($this->config->validateRequests) {
            $request->validate();
        }

        // Build SOAP XML with auth headers
        $soapXml = $request->toSoapXml($this->config);

        // Create PSR-7 request
        $endpointUrl = $this->config->getEndpoint($request->getEndpoint());
        $psrRequest = $this->requestFactory->createRequest('POST', $endpointUrl);
        $psrRequest = $psrRequest->withHeader('Content-Type', 'text/xml; charset=utf-8');

        $soapAction = $request->getSoapAction();
        if ($soapAction !== null) {
            $psrRequest = $psrRequest->withHeader('SOAPAction', $soapAction);
        }

        $psrRequest = $psrRequest->withBody($this->streamFactory->createStream($soapXml));

        // Execute cached middleware pipeline
        $response = $this->pipeline->handle($psrRequest, $this->sendRequest(...));

        // Check for HTTP errors
        $statusCode = $response->getStatusCode();
        $responseBody = $response->getBody();
        if ($responseBody->isSeekable()) {
            $responseBody->rewind();
        }
        $body = (string) $responseBody;
        // Replace the body with a fresh seekable stream for downstream consumers
        $response = $response->withBody($this->streamFactory->createStream($body));

        if (\in_array($statusCode, [401, 403], true)) {
            throw new AuthenticationException(
                'Authentication failed',
                $statusCode,
                $body,
            );
        }

        if ($statusCode >= 400) {
            throw new HttpException(
                'HTTP error ' . $statusCode,
                $statusCode,
                $body,
            );
        }

        return $response;
    }

    /**
     * Send a request, parse the SOAP envelope, check for faults,
     * and return the pre-parsed SOAP Body element.
     *
     * @throws ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws AuthenticationException
     * @throws HttpException
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function sendAndParse(Request $request): \SimpleXMLElement
    {
        $response = $this->send($request);
        $responseBody = $response->getBody();
        if ($responseBody->isSeekable()) {
            $responseBody->rewind();
        }
        $envelope = SoapEnvelope::parse((string) $responseBody);

        $errorResponse = $envelope->getFault();
        if ($errorResponse !== null) {
            throw new ApiException(
                'SOAP Fault: ' . $errorResponse->faultString . ' (' . $errorResponse->faultCode . ')',
                $errorResponse,
            );
        }

        return $envelope->getBody();
    }

    private function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->httpClient->sendRequest($request);
    }

    private function buildPipeline(): MiddlewarePipeline
    {
        $pipeline = new MiddlewarePipeline();

        if ($this->config->logger !== null) {
            $pipeline->add(new LoggingMiddleware($this->config->logger));
        }

        foreach ($this->middleware as $mw) {
            $pipeline->add($mw);
        }

        return $pipeline;
    }

    private static function discoverHttpClient(int $timeout): ClientInterface
    {
        // When Guzzle is available, create it directly so we can pass the timeout
        if (class_exists(\GuzzleHttp\Client::class)) {
            return new \GuzzleHttp\Client(['timeout' => $timeout]);
        }

        // Note: The timeout from Config cannot be applied to clients discovered
        // via PSR-18 discovery. For timeout control, inject a pre-configured
        // ClientInterface instance into the Connector constructor.
        if (class_exists(\Http\Discovery\Psr18ClientDiscovery::class)) {
            try {
                return \Http\Discovery\Psr18ClientDiscovery::find();
            } catch (\Http\Discovery\Exception\NotFoundException $e) {
                // Fall through
            }
        }

        throw new ConfigurationException(
            'No PSR-18 HTTP client found. Install a PSR-18 implementation or provide a ClientInterface instance.'
        );
    }

    private static function discoverRequestFactory(): RequestFactoryInterface
    {
        if (class_exists(\Http\Discovery\Psr17FactoryDiscovery::class)) {
            try {
                return \Http\Discovery\Psr17FactoryDiscovery::findRequestFactory();
            } catch (\Http\Discovery\Exception\NotFoundException $e) {
                // Fall through
            }
        }

        if (class_exists(\GuzzleHttp\Psr7\HttpFactory::class)) {
            return new \GuzzleHttp\Psr7\HttpFactory();
        }

        throw new ConfigurationException(
            'No PSR-17 RequestFactory found. Install a PSR-17 implementation or provide a RequestFactoryInterface instance.'
        );
    }

    private static function discoverStreamFactory(): StreamFactoryInterface
    {
        if (class_exists(\Http\Discovery\Psr17FactoryDiscovery::class)) {
            try {
                return \Http\Discovery\Psr17FactoryDiscovery::findStreamFactory();
            } catch (\Http\Discovery\Exception\NotFoundException $e) {
                // Fall through
            }
        }

        if (class_exists(\GuzzleHttp\Psr7\HttpFactory::class)) {
            return new \GuzzleHttp\Psr7\HttpFactory();
        }

        throw new ConfigurationException(
            'No PSR-17 StreamFactory found. Install a PSR-17 implementation or provide a StreamFactoryInterface instance.'
        );
    }
}
