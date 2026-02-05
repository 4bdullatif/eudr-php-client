<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Requests\V2;

use Eudr\Exceptions\ValidationException;
use Eudr\Requests\V2\RetractDdsRequest;
use Eudr\Support\Xml;
use Eudr\Tests\TestCase;

final class RetractDdsRequestTest extends TestCase
{
    public function testMakeReturnsInstance(): void
    {
        $request = RetractDdsRequest::make();
        $this->assertInstanceOf(RetractDdsRequest::class, $request);
    }

    public function testGetEndpointReturnsV2SubmissionService(): void
    {
        $request = RetractDdsRequest::make();
        $this->assertSame('EUDRSubmissionServiceV2', $request->getEndpoint());
    }

    public function testGetSoapActionReturnsRetractAction(): void
    {
        $request = RetractDdsRequest::make();
        $this->assertSame(
            Xml::EUDR_SUBMISSION_NS_V2 . '/retractDds',
            $request->getSoapAction()
        );
    }

    public function testValidateRequiresDdsIdentifier(): void
    {
        $request = RetractDdsRequest::make();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('DDS identifier is required for retract');
        $request->validate();
    }

    public function testValidateRejectsEmptyDdsIdentifier(): void
    {
        $request = RetractDdsRequest::make()
            ->withDdsIdentifier('');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('DDS identifier is required for retract');
        $request->validate();
    }

    public function testValidatePassesForValidRequest(): void
    {
        $request = RetractDdsRequest::make()
            ->withDdsIdentifier('EUDR.2024.DE.123');

        $request->validate();
        $this->addToAssertionCount(1);
    }

    public function testToSoapXmlProducesValidXmlWithRetractStatementRequest(): void
    {
        $request = RetractDdsRequest::make()
            ->withDdsIdentifier('EUDR.2024.DE.789');

        $xml = $request->toSoapXml($this->createConfig());

        $doc = new \DOMDocument();
        $result = $doc->loadXML($xml);
        $this->assertTrue($result, 'Generated XML should be valid');

        $this->assertStringContainsString('v2:RetractStatementRequest', $xml);
        $this->assertStringContainsString('v2:ddsIdentifier', $xml);
        $this->assertStringContainsString('EUDR.2024.DE.789', $xml);
    }

    public function testToSoapXmlContainsCorrectNamespace(): void
    {
        $request = RetractDdsRequest::make()
            ->withDdsIdentifier('EUDR.2024.DE.123');

        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString(Xml::EUDR_SUBMISSION_NS_V2, $xml);
    }

    public function testToSoapXmlContainsSoapEnvelope(): void
    {
        $request = RetractDdsRequest::make()
            ->withDdsIdentifier('EUDR.2024.DE.123');

        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('soapenv:Envelope', $xml);
        $this->assertStringContainsString('soapenv:Header', $xml);
        $this->assertStringContainsString('soapenv:Body', $xml);
    }

    public function testWithDdsIdentifierIsImmutable(): void
    {
        $request1 = RetractDdsRequest::make();
        $request2 = $request1->withDdsIdentifier('EUDR.2024.DE.123');

        $this->assertNotSame($request1, $request2);
    }
}
