<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Requests;

use Eudr\Exceptions\ValidationException;
use Eudr\Requests\EchoRequest;
use Eudr\Support\Xml;
use Eudr\Tests\TestCase;

final class EchoRequestTest extends TestCase
{
    public function testMakeReturnsInstanceWithDefaultQuery(): void
    {
        $request = EchoRequest::make();
        $this->assertInstanceOf(EchoRequest::class, $request);
    }

    public function testMakeAcceptsCustomQuery(): void
    {
        $request = EchoRequest::make('test query');
        $xml = $request->toSoapXml($this->createConfig());
        $this->assertStringContainsString('test query', $xml);
    }

    public function testGetEndpointReturnsEchoService(): void
    {
        $request = EchoRequest::make();
        $this->assertSame('EudrEchoService', $request->getEndpoint());
    }

    public function testGetSoapActionReturnsEchoNamespace(): void
    {
        $request = EchoRequest::make();
        $this->assertSame(Xml::EUDR_ECHO_NS, $request->getSoapAction());
    }

    public function testValidateRejectsEmptyQuery(): void
    {
        $request = EchoRequest::make('');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Echo query cannot be empty');
        $request->validate();
    }

    public function testValidatePassesForNonEmptyQuery(): void
    {
        $request = EchoRequest::make('hello');
        $request->validate();
        $this->addToAssertionCount(1);
    }

    public function testToSoapXmlProducesValidXmlWithEudrEchoRequest(): void
    {
        $request = EchoRequest::make('ping');

        $xml = $request->toSoapXml($this->createConfig());

        $doc = new \DOMDocument();
        $result = $doc->loadXML($xml);
        $this->assertTrue($result, 'Generated XML should be valid');

        $this->assertStringContainsString('echo:EudrEchoRequest', $xml);
        $this->assertStringContainsString('echo:query', $xml);
        $this->assertStringContainsString('ping', $xml);
    }

    public function testToSoapXmlContainsEchoNamespace(): void
    {
        $request = EchoRequest::make();

        $xml = $request->toSoapXml($this->createConfig());
        $this->assertStringContainsString(Xml::EUDR_ECHO_NS, $xml);
    }

    public function testToSoapXmlContainsDefaultQueryHello(): void
    {
        $request = EchoRequest::make();

        $xml = $request->toSoapXml($this->createConfig());
        $this->assertStringContainsString('hello', $xml);
    }

    public function testWithQueryIsImmutable(): void
    {
        $request1 = EchoRequest::make('first');
        $request2 = $request1->withQuery('second');

        $this->assertNotSame($request1, $request2);

        $xml1 = $request1->toSoapXml($this->createConfig());
        $xml2 = $request2->toSoapXml($this->createConfig());

        $this->assertStringContainsString('first', $xml1);
        $this->assertStringContainsString('second', $xml2);
        $this->assertStringNotContainsString('second', $xml1);
    }

    public function testToSoapXmlContainsSoapEnvelope(): void
    {
        $request = EchoRequest::make();
        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('soapenv:Envelope', $xml);
        $this->assertStringContainsString('soapenv:Body', $xml);
    }
}
