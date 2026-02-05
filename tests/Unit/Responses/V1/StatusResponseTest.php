<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Responses\V1;

use Eudr\Responses\V1\StatusResponse;
use Eudr\Tests\TestCase;

final class StatusResponseTest extends TestCase
{
    public function testIsSuccessReturnsTrueForSc200Ok(): void
    {
        $response = new StatusResponse(status: 'SC_200_OK');
        $this->assertTrue($response->isSuccess());
    }

    public function testIsSuccessReturnsFalseForOtherStatus(): void
    {
        $response = new StatusResponse(status: 'SC_400_BAD_REQUEST');
        $this->assertFalse($response->isSuccess());
    }

    public function testIsSuccessReturnsFalseForEmptyStatus(): void
    {
        $response = new StatusResponse(status: '');
        $this->assertFalse($response->isSuccess());
    }

    public function testFromXmlParsesAmendStatementResponse(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:AmendStatementResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/submission/v1">
                    <ns2:status>SC_200_OK</ns2:status>
                </ns2:AmendStatementResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = StatusResponse::fromXml($xml);
        $this->assertSame('SC_200_OK', $response->status);
        $this->assertTrue($response->isSuccess());
    }

    public function testFromXmlParsesRetractStatementResponse(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:RetractStatementResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/submission/v1">
                    <ns2:status>SC_200_OK</ns2:status>
                </ns2:RetractStatementResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = StatusResponse::fromXml($xml);
        $this->assertSame('SC_200_OK', $response->status);
        $this->assertTrue($response->isSuccess());
    }

    public function testFromXmlParsesAmendResponseWithErrorStatus(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:AmendStatementResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/submission/v1">
                    <ns2:status>SC_400_BAD_REQUEST</ns2:status>
                </ns2:AmendStatementResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = StatusResponse::fromXml($xml);
        $this->assertSame('SC_400_BAD_REQUEST', $response->status);
        $this->assertFalse($response->isSuccess());
    }

    public function testFromXmlThrowsWhenResponseNotFound(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:SomethingElse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/submission/v1">
                </ns2:SomethingElse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $this->expectException(\Eudr\Exceptions\XmlException::class);
        $this->expectExceptionMessage('Could not find amend/retract response');
        StatusResponse::fromXml($xml);
    }
}
