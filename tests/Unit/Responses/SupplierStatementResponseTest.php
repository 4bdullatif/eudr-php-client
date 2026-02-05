<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Responses;

use Eudr\Enums\DdsStatus;
use Eudr\Exceptions\XmlException;
use Eudr\Responses\SupplierStatementResponse;
use Eudr\Tests\TestCase;

final class SupplierStatementResponseTest extends TestCase
{
    public function testFromXmlParsesGetStatementByIdentifiersResponse(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:GetStatementByIdentifiersResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v2">
                    <ns2:statement>
                        <ns2:referenceNumber>REF-2024-001</ns2:referenceNumber>
                        <ns2:activityType>IMPORT</ns2:activityType>
                    </ns2:statement>
                </ns2:GetStatementByIdentifiersResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = SupplierStatementResponse::fromXml($xml);
        $this->assertSame('REF-2024-001', $response->referenceNumber);
        $this->assertSame('IMPORT', $response->activityType);
        $this->assertTrue($response->isSuccess());
    }

    public function testFromXmlParsesNestedStatusWithDate(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:GetStatementByIdentifiersResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v2">
                    <ns2:statement>
                        <ns2:referenceNumber>REF-2024-002</ns2:referenceNumber>
                        <ns2:status>
                            <ns2:status>SUBMITTED</ns2:status>
                            <ns2:date>2024-07-15T10:30:00Z</ns2:date>
                        </ns2:status>
                    </ns2:statement>
                </ns2:GetStatementByIdentifiersResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = SupplierStatementResponse::fromXml($xml);
        $this->assertSame('REF-2024-002', $response->referenceNumber);
        $this->assertSame(DdsStatus::SUBMITTED, $response->status);
        $this->assertSame('SUBMITTED', $response->rawStatusValue);
        $this->assertSame('2024-07-15T10:30:00Z', $response->statusDate);
    }

    public function testFromXmlParsesOperatorInfo(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:GetStatementByIdentifiersResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v2">
                    <ns2:statement>
                        <ns2:referenceNumber>REF-2024-003</ns2:referenceNumber>
                        <ns2:operator>
                            <ns2:name>Test Corporation</ns2:name>
                            <ns2:country>DE</ns2:country>
                        </ns2:operator>
                    </ns2:statement>
                </ns2:GetStatementByIdentifiersResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = SupplierStatementResponse::fromXml($xml);
        $this->assertSame('REF-2024-003', $response->referenceNumber);
        $this->assertSame('Test Corporation', $response->operatorName);
        $this->assertSame('DE', $response->operatorCountry);
    }

    public function testFromXmlParsesAssociatedStatements(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:GetStatementByIdentifiersResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v2">
                    <ns2:statement>
                        <ns2:referenceNumber>REF-2024-004</ns2:referenceNumber>
                        <ns2:associateStatement>
                            <ns2:referenceNumber>ASSOC-REF-001</ns2:referenceNumber>
                        </ns2:associateStatement>
                        <ns2:associateStatement>
                            <ns2:referenceNumber>ASSOC-REF-002</ns2:referenceNumber>
                        </ns2:associateStatement>
                    </ns2:statement>
                </ns2:GetStatementByIdentifiersResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = SupplierStatementResponse::fromXml($xml);
        $this->assertSame('REF-2024-004', $response->referenceNumber);
        $this->assertCount(2, $response->associatedStatements);
        $this->assertSame('ASSOC-REF-001', $response->associatedStatements[0]['referenceNumber']);
        $this->assertSame('ASSOC-REF-002', $response->associatedStatements[1]['referenceNumber']);
    }

    public function testFromXmlParsesGetReferencedDdsResponse(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:GetReferencedDdsResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v2">
                    <ns2:referenceDds>
                        <ns2:referenceNumber>REF-REFERENCED-001</ns2:referenceNumber>
                        <ns2:activityType>EXPORT</ns2:activityType>
                    </ns2:referenceDds>
                </ns2:GetReferencedDdsResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = SupplierStatementResponse::fromXml($xml);
        $this->assertSame('REF-REFERENCED-001', $response->referenceNumber);
        $this->assertSame('EXPORT', $response->activityType);
    }

    public function testIsSuccessReturnsTrueWhenReferenceNumberPresent(): void
    {
        $response = new SupplierStatementResponse(referenceNumber: 'REF-001');
        $this->assertTrue($response->isSuccess());
    }

    public function testIsSuccessReturnsFalseWhenReferenceNumberNull(): void
    {
        $response = new SupplierStatementResponse();
        $this->assertFalse($response->isSuccess());
    }

    public function testFromXmlThrowsWhenResponseNotFound(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:SomethingElse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v2">
                </ns2:SomethingElse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('Could not find supplier statement response');
        SupplierStatementResponse::fromXml($xml);
    }

    public function testFromXmlHandlesUnknownStatus(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:GetStatementByIdentifiersResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v2">
                    <ns2:statement>
                        <ns2:referenceNumber>REF-2024-005</ns2:referenceNumber>
                        <ns2:status>
                            <ns2:status>NEW_UNKNOWN_STATUS</ns2:status>
                        </ns2:status>
                    </ns2:statement>
                </ns2:GetStatementByIdentifiersResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = SupplierStatementResponse::fromXml($xml);
        $this->assertSame(DdsStatus::UNKNOWN, $response->status);
        $this->assertSame('NEW_UNKNOWN_STATUS', $response->rawStatusValue);
    }
}
