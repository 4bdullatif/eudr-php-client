<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Responses\V2;

use Eudr\Enums\DdsStatus;
use Eudr\Responses\V2\RetrieveDdsResponse;
use Eudr\Tests\TestCase;

final class RetrieveDdsResponseMultiTest extends TestCase
{
    public function testAllFromXmlReturnsMultipleStatementInfoElements(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:GetStatementInfoResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v2">
                    <ns2:statementInfo>
                        <ns2:identifier>EUDR.2024.DE.001</ns2:identifier>
                        <ns2:internalReferenceNumber>INT-001</ns2:internalReferenceNumber>
                        <ns2:referenceNumber>REF-001</ns2:referenceNumber>
                        <ns2:status>SUBMITTED</ns2:status>
                    </ns2:statementInfo>
                    <ns2:statementInfo>
                        <ns2:identifier>EUDR.2024.DE.002</ns2:identifier>
                        <ns2:internalReferenceNumber>INT-002</ns2:internalReferenceNumber>
                        <ns2:referenceNumber>REF-002</ns2:referenceNumber>
                        <ns2:status>AVAILABLE</ns2:status>
                    </ns2:statementInfo>
                    <ns2:statementInfo>
                        <ns2:identifier>EUDR.2024.DE.003</ns2:identifier>
                        <ns2:status>REJECTED</ns2:status>
                    </ns2:statementInfo>
                </ns2:GetStatementInfoResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $results = RetrieveDdsResponse::allFromXml($xml);

        $this->assertCount(3, $results);

        $this->assertSame('EUDR.2024.DE.001', $results[0]->identifier);
        $this->assertSame('INT-001', $results[0]->internalReferenceNumber);
        $this->assertSame('REF-001', $results[0]->referenceNumber);
        $this->assertSame(DdsStatus::SUBMITTED, $results[0]->status);

        $this->assertSame('EUDR.2024.DE.002', $results[1]->identifier);
        $this->assertSame('INT-002', $results[1]->internalReferenceNumber);
        $this->assertSame('REF-002', $results[1]->referenceNumber);
        $this->assertSame(DdsStatus::AVAILABLE, $results[1]->status);

        $this->assertSame('EUDR.2024.DE.003', $results[2]->identifier);
        $this->assertSame(DdsStatus::REJECTED, $results[2]->status);
    }

    public function testAllFromXmlReturnsEmptyArrayWhenNoResponseFound(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:SomeOtherResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v2">
                    <ns2:data>nothing</ns2:data>
                </ns2:SomeOtherResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $results = RetrieveDdsResponse::allFromXml($xml);
        $this->assertSame([], $results);
    }

    public function testFromXmlStillReturnsFirstResult(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:GetStatementInfoResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v2">
                    <ns2:statementInfo>
                        <ns2:identifier>EUDR.2024.DE.FIRST</ns2:identifier>
                        <ns2:status>SUBMITTED</ns2:status>
                    </ns2:statementInfo>
                    <ns2:statementInfo>
                        <ns2:identifier>EUDR.2024.DE.SECOND</ns2:identifier>
                        <ns2:status>AVAILABLE</ns2:status>
                    </ns2:statementInfo>
                </ns2:GetStatementInfoResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = RetrieveDdsResponse::fromXml($xml);
        $this->assertSame('EUDR.2024.DE.FIRST', $response->identifier);
        $this->assertSame(DdsStatus::SUBMITTED, $response->status);
    }

    public function testAllFromXmlReturnsSingleElement(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:GetStatementInfoResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v2">
                    <ns2:statementInfo>
                        <ns2:identifier>EUDR.2024.DE.ONLY</ns2:identifier>
                        <ns2:status>AVAILABLE</ns2:status>
                    </ns2:statementInfo>
                </ns2:GetStatementInfoResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $results = RetrieveDdsResponse::allFromXml($xml);
        $this->assertCount(1, $results);
        $this->assertSame('EUDR.2024.DE.ONLY', $results[0]->identifier);
    }
}
