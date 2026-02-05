<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Responses\V2;

use Eudr\Enums\DdsStatus;
use Eudr\Exceptions\XmlException;
use Eudr\Responses\V2\RetrieveDdsResponse;
use Eudr\Tests\TestCase;

final class RetrieveDdsResponseTest extends TestCase
{
    public function testFromXmlParsesFixture(): void
    {
        $xml = $this->fixture('retrieve_response_v2.xml');
        $response = RetrieveDdsResponse::fromXml($xml);

        $this->assertSame('EUDR.2024.DE.1234567890', $response->identifier);
        $this->assertSame('INT-REF-001', $response->internalReferenceNumber);
        $this->assertSame('REF-2024-001', $response->referenceNumber);
        $this->assertSame('VER-2024-001', $response->verificationNumber);
        $this->assertSame(DdsStatus::SUBMITTED, $response->status);
        $this->assertSame('SUBMITTED', $response->rawStatusValue);
        $this->assertSame('user@example.com', $response->updatedBy);
    }

    public function testIsSuccessReturnsTrueWhenIdentifierPresent(): void
    {
        $response = new RetrieveDdsResponse(identifier: 'EUDR.2024.DE.123');
        $this->assertTrue($response->isSuccess());
    }

    public function testIsSuccessReturnsFalseWhenIdentifierEmpty(): void
    {
        $response = new RetrieveDdsResponse(identifier: '');
        $this->assertFalse($response->isSuccess());
    }

    public function testFromXmlThrowsOnInvalidXml(): void
    {
        $this->expectException(XmlException::class);
        RetrieveDdsResponse::fromXml('not xml');
    }

    public function testFromXmlThrowsOnMissingSoapBody(): void
    {
        $this->expectException(XmlException::class);
        RetrieveDdsResponse::fromXml('<?xml version="1.0"?><root/>');
    }

    public function testFromXmlMapsUnknownStatus(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:GetStatementInfoResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v2">
                    <ns2:statementInfo>
                        <ns2:identifier>EUDR.2024.DE.123</ns2:identifier>
                        <ns2:status>SOME_NEW_STATUS</ns2:status>
                    </ns2:statementInfo>
                </ns2:GetStatementInfoResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = RetrieveDdsResponse::fromXml($xml);
        $this->assertSame(DdsStatus::UNKNOWN, $response->status);
        $this->assertSame('SOME_NEW_STATUS', $response->rawStatusValue);
    }

    public function testFromXmlHandlesAvailableStatus(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:GetStatementInfoResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v2">
                    <ns2:statementInfo>
                        <ns2:identifier>EUDR.2024.DE.456</ns2:identifier>
                        <ns2:status>AVAILABLE</ns2:status>
                    </ns2:statementInfo>
                </ns2:GetStatementInfoResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = RetrieveDdsResponse::fromXml($xml);
        $this->assertSame(DdsStatus::AVAILABLE, $response->status);
    }

    public function testFromXmlParsesGetDdsInfoByInternalReferenceNumberResponse(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:GetDdsInfoByInternalReferenceNumberResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v2">
                    <ns2:statementInfo>
                        <ns2:identifier>EUDR.2024.DE.555</ns2:identifier>
                        <ns2:internalReferenceNumber>FR-0000001</ns2:internalReferenceNumber>
                        <ns2:status>SUBMITTED</ns2:status>
                        <ns2:rejectionReason>Missing documents</ns2:rejectionReason>
                        <ns2:date>2024-06-15</ns2:date>
                    </ns2:statementInfo>
                </ns2:GetDdsInfoByInternalReferenceNumberResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = RetrieveDdsResponse::fromXml($xml);
        $this->assertSame('EUDR.2024.DE.555', $response->identifier);
        $this->assertSame('FR-0000001', $response->internalReferenceNumber);
        $this->assertSame(DdsStatus::SUBMITTED, $response->status);
        $this->assertSame('Missing documents', $response->rejectionReason);
        $this->assertSame('2024-06-15', $response->date);
    }

    public function testFromXmlParsesRejectionReasonAndDate(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:GetStatementInfoResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v2">
                    <ns2:statementInfo>
                        <ns2:identifier>EUDR.2024.DE.999</ns2:identifier>
                        <ns2:status>REJECTED</ns2:status>
                        <ns2:rejectionReason>Invalid commodity code</ns2:rejectionReason>
                        <ns2:date>2024-07-20</ns2:date>
                    </ns2:statementInfo>
                </ns2:GetStatementInfoResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = RetrieveDdsResponse::fromXml($xml);
        $this->assertSame('Invalid commodity code', $response->rejectionReason);
        $this->assertSame('2024-07-20', $response->date);
    }
}
