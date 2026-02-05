<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Requests\V1;

use Eudr\Exceptions\ValidationException;
use Eudr\Requests\V1\GetStatementByIdentifiersRequest;
use Eudr\Support\Xml;
use Eudr\Tests\TestCase;

final class GetStatementByIdentifiersRequestTest extends TestCase
{
    public function testMakeReturnsInstance(): void
    {
        $request = GetStatementByIdentifiersRequest::make();
        $this->assertInstanceOf(GetStatementByIdentifiersRequest::class, $request);
    }

    public function testGetEndpointReturnsV1RetrievalService(): void
    {
        $request = GetStatementByIdentifiersRequest::make();
        $this->assertSame('EUDRRetrievalServiceV1', $request->getEndpoint());
    }

    public function testGetSoapActionReturnsCorrectAction(): void
    {
        $request = GetStatementByIdentifiersRequest::make();
        $this->assertSame(
            Xml::EUDR_RETRIEVAL_NS_V1 . '/getStatementByIdentifiers',
            $request->getSoapAction()
        );
    }

    public function testValidateRequiresReferenceNumber(): void
    {
        $request = GetStatementByIdentifiersRequest::make()
            ->withVerificationNumber('VER-001');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Reference number is required');
        $request->validate();
    }

    public function testValidateRejectsEmptyReferenceNumber(): void
    {
        $request = GetStatementByIdentifiersRequest::make()
            ->withReferenceNumber('')
            ->withVerificationNumber('VER-001');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Reference number is required');
        $request->validate();
    }

    public function testValidateRequiresVerificationNumber(): void
    {
        $request = GetStatementByIdentifiersRequest::make()
            ->withReferenceNumber('REF-001');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Verification number is required');
        $request->validate();
    }

    public function testValidateRejectsEmptyVerificationNumber(): void
    {
        $request = GetStatementByIdentifiersRequest::make()
            ->withReferenceNumber('REF-001')
            ->withVerificationNumber('');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Verification number is required');
        $request->validate();
    }

    public function testValidatePassesForValidRequest(): void
    {
        $request = GetStatementByIdentifiersRequest::make()
            ->withReferenceNumber('REF-001')
            ->withVerificationNumber('VER-001');

        $request->validate();
        $this->addToAssertionCount(1);
    }

    public function testToSoapXmlProducesValidXml(): void
    {
        $request = GetStatementByIdentifiersRequest::make()
            ->withReferenceNumber('REF-2024-001')
            ->withVerificationNumber('VER-2024-001');

        $xml = $request->toSoapXml($this->createConfig());

        $doc = new \DOMDocument();
        $result = $doc->loadXML($xml);
        $this->assertTrue($result, 'Generated XML should be valid');

        $this->assertStringContainsString('v1:GetStatementByIdentifiersRequest', $xml);
        $this->assertStringContainsString('v1:referenceNumber', $xml);
        $this->assertStringContainsString('REF-2024-001', $xml);
        $this->assertStringContainsString('v1:verificationNumber', $xml);
        $this->assertStringContainsString('VER-2024-001', $xml);
    }

    public function testToSoapXmlContainsCorrectNamespace(): void
    {
        $request = GetStatementByIdentifiersRequest::make()
            ->withReferenceNumber('REF-001')
            ->withVerificationNumber('VER-001');

        $xml = $request->toSoapXml($this->createConfig());
        $this->assertStringContainsString(Xml::EUDR_RETRIEVAL_NS_V1, $xml);
    }

    public function testWithReferenceNumberIsImmutable(): void
    {
        $request1 = GetStatementByIdentifiersRequest::make();
        $request2 = $request1->withReferenceNumber('REF-001');
        $this->assertNotSame($request1, $request2);
    }

    public function testWithVerificationNumberIsImmutable(): void
    {
        $request1 = GetStatementByIdentifiersRequest::make();
        $request2 = $request1->withVerificationNumber('VER-001');
        $this->assertNotSame($request1, $request2);
    }
}
