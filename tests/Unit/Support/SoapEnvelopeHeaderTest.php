<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Support;

use Eudr\Config\Config;
use Eudr\Config\Credentials;
use Eudr\Http\Connector;
use Eudr\Requests\EchoRequest;
use Eudr\Support\SoapEnvelope;
use Eudr\Tests\TestCase;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Http\Mock\Client as MockClient;

final class SoapEnvelopeHeaderTest extends TestCase
{
    public function testHeaderIsExposedWhenPresent(): void
    {
        $envelope = SoapEnvelope::parse($this->fixture('v4/submit_dds_response.xml'));

        $header = $envelope->getHeader();
        $this->assertNotNull($header);
        $this->assertSame('Header', $header->getName());
        $this->assertSame('SubmitDdsResponse', $envelope->getBody()->children('http://ec.europa.eu/tracesnt/certificate/eudr/due-diligence-statement/v4')->getName());
    }

    public function testHeaderIsNullWhenAbsent(): void
    {
        $this->assertNull(SoapEnvelope::parse($this->fixture('submit_response_v2.xml'))->getHeader());
    }

    public function testSendAndParseReturnsTheEnvelopeBody(): void
    {
        $http = new MockClient();
        $http->addResponse(new Response(200, [], $this->fixture('echo_response.xml')));
        $http->addResponse(new Response(200, [], $this->fixture('echo_response.xml')));

        $connector = new Connector(
            new Config('https://example.test/ws', new Credentials('u', 'k', 'c')),
            $http,
            new HttpFactory(),
            new HttpFactory(),
        );

        $body = $connector->sendAndParse(EchoRequest::make());
        $envelope = $connector->sendAndParseEnvelope(EchoRequest::make());

        $this->assertSame($body->asXML(), $envelope->getBody()->asXML());
    }
}
