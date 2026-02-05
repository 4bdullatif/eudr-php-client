<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Http;

use Eudr\Data\Commodity;
use Eudr\Data\Producer;
use Eudr\Data\SpeciesInfo;
use Eudr\Enums\ActivityType;
use Eudr\Enums\OperatorType;
use Eudr\Exceptions\ApiException;
use Eudr\Exceptions\AuthenticationException;
use Eudr\Exceptions\HttpException;
use Eudr\Http\Connector;
use Eudr\Requests\V2\SubmitDdsRequest;
use Eudr\Tests\TestCase;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Http\Mock\Client as MockClient;

final class ConnectorTest extends TestCase
{
    public function testSendCreatesPostRequest(): void
    {
        $mockClient = new MockClient();
        $submitXml = $this->fixture('submit_response_v2.xml');
        $mockClient->addResponse(new Response(200, ['Content-Type' => 'text/xml'], $submitXml));

        $connector = new Connector(
            config: $this->createConfig(),
            httpClient: $mockClient,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );

        $request = $this->createValidSubmitRequest();
        $response = $connector->send($request);

        $this->assertSame(200, $response->getStatusCode());

        $sentRequests = $mockClient->getRequests();
        $this->assertCount(1, $sentRequests);

        $sentRequest = $sentRequests[0];
        $this->assertSame('POST', $sentRequest->getMethod());
        $this->assertStringContainsString('EUDRSubmissionServiceV2', (string) $sentRequest->getUri());
        $this->assertStringContainsString('text/xml', $sentRequest->getHeaderLine('Content-Type'));
    }

    public function testSendThrowsAuthenticationExceptionOn401(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(new Response(401, [], 'Unauthorized'));

        $connector = new Connector(
            config: $this->createConfig(),
            httpClient: $mockClient,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );

        $this->expectException(AuthenticationException::class);
        $connector->send($this->createValidSubmitRequest());
    }

    public function testSendThrowsAuthenticationExceptionOn403(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(new Response(403, [], 'Forbidden'));

        $connector = new Connector(
            config: $this->createConfig(),
            httpClient: $mockClient,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );

        $this->expectException(AuthenticationException::class);
        $connector->send($this->createValidSubmitRequest());
    }

    public function testSendThrowsHttpExceptionOn500(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(new Response(500, [], 'Server Error'));

        $connector = new Connector(
            config: $this->createConfig(),
            httpClient: $mockClient,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );

        $this->expectException(HttpException::class);
        $connector->send($this->createValidSubmitRequest());
    }

    public function testSendIncludesSoapXmlInBody(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(new Response(200, [], $this->fixture('submit_response_v2.xml')));

        $connector = new Connector(
            config: $this->createConfig(),
            httpClient: $mockClient,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );

        $connector->send($this->createValidSubmitRequest());

        $sentRequest = $mockClient->getRequests()[0];
        $body = (string) $sentRequest->getBody();

        $this->assertStringContainsString('soapenv:Envelope', $body);
        $this->assertStringContainsString('SubmitStatementRequest', $body);
        $this->assertStringContainsString('wsse:Security', $body);
    }

    public function testSendAndParseReturnsSimpleXmlElementBody(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(new Response(200, ['Content-Type' => 'text/xml'], $this->fixture('submit_response_v2.xml')));

        $connector = new Connector(
            config: $this->createConfig(),
            httpClient: $mockClient,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );

        $body = $connector->sendAndParse($this->createValidSubmitRequest());

        $this->assertInstanceOf(\SimpleXMLElement::class, $body);
    }

    public function testSendAndParseThrowsApiExceptionOnSoapFault(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(new Response(200, ['Content-Type' => 'text/xml'], $this->fixture('soap_fault.xml')));

        $connector = new Connector(
            config: $this->createConfig(),
            httpClient: $mockClient,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('SOAP Fault');
        $connector->sendAndParse($this->createValidSubmitRequest());
    }

    private function createValidSubmitRequest(): SubmitDdsRequest
    {
        return SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity(new Commodity(
                position: 1,
                description: 'Test commodity',
                hsHeading: '4403',
                speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
                producers: [new Producer('DE', 'base64geojson')],
            ));
    }
}
