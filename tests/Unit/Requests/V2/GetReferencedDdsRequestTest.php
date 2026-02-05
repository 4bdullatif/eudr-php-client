<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Requests\V2;

use Eudr\Exceptions\ValidationException;
use Eudr\Requests\V2\GetReferencedDdsRequest;
use Eudr\Support\Xml;
use Eudr\Tests\TestCase;

final class GetReferencedDdsRequestTest extends TestCase
{
    public function testMakeReturnsInstance(): void
    {
        $request = GetReferencedDdsRequest::make();
        $this->assertInstanceOf(GetReferencedDdsRequest::class, $request);
    }

    public function testGetEndpointReturnsV2RetrievalService(): void
    {
        $request = GetReferencedDdsRequest::make();
        $this->assertSame('EUDRRetrievalServiceV2', $request->getEndpoint());
    }

    public function testGetSoapActionReturnsCorrectAction(): void
    {
        $request = GetReferencedDdsRequest::make();
        $this->assertSame(
            Xml::EUDR_RETRIEVAL_NS_V2 . '/getReferencedDds',
            $request->getSoapAction()
        );
    }

    public function testValidateRequiresReferenceNumber(): void
    {
        $request = GetReferencedDdsRequest::make()
            ->withReferenceDdsVerificationNumber('VER-001');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Reference number is required');
        $request->validate();
    }

    public function testValidateRejectsEmptyReferenceNumber(): void
    {
        $request = GetReferencedDdsRequest::make()
            ->withReferenceNumber('')
            ->withReferenceDdsVerificationNumber('VER-001');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Reference number is required');
        $request->validate();
    }

    public function testValidateRequiresReferenceDdsVerificationNumber(): void
    {
        $request = GetReferencedDdsRequest::make()
            ->withReferenceNumber('REF-001');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Reference DDS verification number is required');
        $request->validate();
    }

    public function testValidateRejectsEmptyReferenceDdsVerificationNumber(): void
    {
        $request = GetReferencedDdsRequest::make()
            ->withReferenceNumber('REF-001')
            ->withReferenceDdsVerificationNumber('');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Reference DDS verification number is required');
        $request->validate();
    }

    public function testValidatePassesForValidRequest(): void
    {
        $request = GetReferencedDdsRequest::make()
            ->withReferenceNumber('REF-001')
            ->withReferenceDdsVerificationNumber('VER-001');

        $request->validate();
        $this->addToAssertionCount(1);
    }

    public function testToSoapXmlProducesValidXml(): void
    {
        $request = GetReferencedDdsRequest::make()
            ->withReferenceNumber('REF-2024-001')
            ->withReferenceDdsVerificationNumber('VER-2024-001');

        $xml = $request->toSoapXml($this->createConfig());

        $doc = new \DOMDocument();
        $result = $doc->loadXML($xml);
        $this->assertTrue($result, 'Generated XML should be valid');

        $this->assertStringContainsString('v2:GetReferencedDdsRequest', $xml);
        $this->assertStringContainsString('v2:referenceNumber', $xml);
        $this->assertStringContainsString('REF-2024-001', $xml);
        $this->assertStringContainsString('v2:referenceDdsVerificationNumber', $xml);
        $this->assertStringContainsString('VER-2024-001', $xml);
    }

    public function testToSoapXmlContainsCorrectNamespace(): void
    {
        $request = GetReferencedDdsRequest::make()
            ->withReferenceNumber('REF-001')
            ->withReferenceDdsVerificationNumber('VER-001');

        $xml = $request->toSoapXml($this->createConfig());
        $this->assertStringContainsString(Xml::EUDR_RETRIEVAL_NS_V2, $xml);
    }

    public function testWithReferenceNumberIsImmutable(): void
    {
        $request1 = GetReferencedDdsRequest::make();
        $request2 = $request1->withReferenceNumber('REF-001');
        $this->assertNotSame($request1, $request2);
    }

    public function testWithReferenceDdsVerificationNumberIsImmutable(): void
    {
        $request1 = GetReferencedDdsRequest::make();
        $request2 = $request1->withReferenceDdsVerificationNumber('VER-001');
        $this->assertNotSame($request1, $request2);
    }
}
