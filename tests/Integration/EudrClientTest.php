<?php

declare(strict_types=1);

namespace Eudr\Tests\Integration;

use Eudr\EudrClient;
use Eudr\Exceptions\ApiException;
use Eudr\Tests\TestCase;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Http\Mock\Client as MockClient;

final class EudrClientTest extends TestCase
{
    public function testEchoReturnsSuccessMessage(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(
            new Response(200, ['Content-Type' => 'text/xml'], $this->fixture('echo_response.xml')),
        );

        $client = new EudrClient(
            config: $this->createConfig(),
            httpClient: $mockClient,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );

        $response = $client->echo('hello');

        $this->assertTrue($response->isSuccess());
        $this->assertSame('hello', $response->result);
    }

    public function testEchoThrowsApiExceptionOnSoapFault(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(
            new Response(200, ['Content-Type' => 'text/xml'], $this->fixture('soap_fault.xml')),
        );

        $client = new EudrClient(
            config: $this->createConfig(),
            httpClient: $mockClient,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Internal Server Error');
        $client->echo('hello');
    }
}
