<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Requests\V1;

use Eudr\Exceptions\ValidationException;
use Eudr\Requests\V1\RetrieveDdsRequest;
use Eudr\Support\Xml;
use Eudr\Tests\TestCase;

final class RetrieveDdsRequestTest extends TestCase
{
    public function testMakeReturnsInstance(): void
    {
        $request = RetrieveDdsRequest::make();
        $this->assertInstanceOf(RetrieveDdsRequest::class, $request);
    }

    public function testFluentBuilderIsImmutable(): void
    {
        $request1 = RetrieveDdsRequest::make();
        $request2 = $request1->withIdentifier('test');

        $this->assertNotSame($request1, $request2);
    }

    public function testGetEndpoint(): void
    {
        $request = RetrieveDdsRequest::make();
        $this->assertSame('EUDRRetrievalServiceV1', $request->getEndpoint());
    }

    public function testValidateRequiresIdentifierOrReference(): void
    {
        $request = RetrieveDdsRequest::make();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('identifier or reference is required');
        $request->validate();
    }

    public function testValidatePassesWithIdentifier(): void
    {
        $request = RetrieveDdsRequest::make()->withIdentifier('EUDR.2024.DE.123');
        $request->validate();
        $this->addToAssertionCount(1);
    }

    public function testValidatePassesWithReference(): void
    {
        $request = RetrieveDdsRequest::make()->withReference('REF-001');
        $request->validate();
        $this->addToAssertionCount(1);
    }

    public function testWithIdentifierClearsReference(): void
    {
        $request = RetrieveDdsRequest::make()
            ->withReference('REF-001')
            ->withIdentifier('EUDR.2024.DE.123');

        $xml = $request->toSoapXml($this->createConfig());
        $this->assertStringContainsString('v1:GetStatementInfoRequest', $xml);
        $this->assertStringContainsString('v1:identifier', $xml);
        $this->assertStringNotContainsString('GetDdsInfoByInternalReferenceNumberRequest', $xml);
    }

    public function testWithReferenceClearsIdentifier(): void
    {
        $request = RetrieveDdsRequest::make()
            ->withIdentifier('EUDR.2024.DE.123')
            ->withReference('REF-001');

        $xml = $request->toSoapXml($this->createConfig());
        $this->assertStringContainsString('v1:GetDdsInfoByInternalReferenceNumberRequest', $xml);
        $this->assertStringContainsString('REF-001', $xml);
        $this->assertStringNotContainsString('v1:GetStatementInfoRequest', $xml);
        $this->assertStringNotContainsString('v1:identifier', $xml);
    }

    public function testReferenceIsDirectTextContent(): void
    {
        $request = RetrieveDdsRequest::make()->withReference('FR-0000001');

        $xml = $request->toSoapXml($this->createConfig());

        // The reference value should be direct text content of the element, not a child element
        $this->assertStringContainsString('>FR-0000001</v1:GetDdsInfoByInternalReferenceNumberRequest>', $xml);
    }

    public function testToSoapXmlContainsSoapEnvelope(): void
    {
        $request = RetrieveDdsRequest::make()->withIdentifier('EUDR.2024.DE.123');
        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('soapenv:Envelope', $xml);
        $this->assertStringContainsString('soapenv:Header', $xml);
        $this->assertStringContainsString('soapenv:Body', $xml);
    }

    public function testToSoapXmlContainsRetrievalNamespace(): void
    {
        $request = RetrieveDdsRequest::make()->withIdentifier('EUDR.2024.DE.123');
        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString(Xml::EUDR_RETRIEVAL_NS_V1, $xml);
    }

    public function testToSoapXmlContainsSecurityHeader(): void
    {
        $request = RetrieveDdsRequest::make()->withIdentifier('EUDR.2024.DE.123');
        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('wsse:Security', $xml);
        $this->assertStringContainsString('wsse:UsernameToken', $xml);
        $this->assertStringContainsString('testuser', $xml);
    }

    public function testToSoapXmlContainsRetrieveBody(): void
    {
        $request = RetrieveDdsRequest::make()->withIdentifier('EUDR.2024.DE.123');
        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('v1:GetStatementInfoRequest', $xml);
        $this->assertStringContainsString('v1:identifier', $xml);
        $this->assertStringContainsString('EUDR.2024.DE.123', $xml);
    }

    public function testToSoapXmlIsValidXml(): void
    {
        $request = RetrieveDdsRequest::make()->withIdentifier('EUDR.2024.DE.123');
        $xml = $request->toSoapXml($this->createConfig());

        $doc = new \DOMDocument();
        $result = $doc->loadXML($xml);
        $this->assertTrue($result);
    }
}
