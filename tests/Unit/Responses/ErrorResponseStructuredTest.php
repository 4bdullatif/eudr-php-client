<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Responses;

use Eudr\Responses\ErrorResponse;
use Eudr\Tests\TestCase;

final class ErrorResponseStructuredTest extends TestCase
{
    public function testFromXmlParsesStructuredErrorDetailsWithIdMessageField(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <soapenv:Fault>
                    <faultcode>soapenv:Server</faultcode>
                    <faultstring>Validation Error</faultstring>
                    <detail>
                        <err:Errors xmlns:err="http://ec.europa.eu/sanco/tracesnt/error/v01">
                            <err:Error>
                                <err:ID>ERR-001</err:ID>
                                <err:Message>Invalid reference number format</err:Message>
                                <err:Field>referenceNumber</err:Field>
                            </err:Error>
                            <err:Error>
                                <err:ID>ERR-002</err:ID>
                                <err:Message>Missing required field</err:Message>
                                <err:Field>operatorType</err:Field>
                            </err:Error>
                        </err:Errors>
                    </detail>
                </soapenv:Fault>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = ErrorResponse::fromXml($xml);

        $this->assertNotNull($response);
        $this->assertSame('soapenv:Server', $response->faultCode);
        $this->assertSame('Validation Error', $response->faultString);
        $this->assertFalse($response->isSuccess());

        $this->assertCount(2, $response->errors);

        $this->assertSame('ERR-001', $response->errors[0]->id);
        $this->assertSame('Invalid reference number format', $response->errors[0]->message);
        $this->assertSame('referenceNumber', $response->errors[0]->field);

        $this->assertSame('ERR-002', $response->errors[1]->id);
        $this->assertSame('Missing required field', $response->errors[1]->message);
        $this->assertSame('operatorType', $response->errors[1]->field);
    }

    public function testErrorsArrayIsEmptyWhenNoStructuredErrorsInFault(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <soapenv:Fault>
                    <faultcode>soapenv:Server</faultcode>
                    <faultstring>Internal Server Error</faultstring>
                    <detail>
                        <message>Something went wrong</message>
                    </detail>
                </soapenv:Fault>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = ErrorResponse::fromXml($xml);

        $this->assertNotNull($response);
        $this->assertSame('soapenv:Server', $response->faultCode);
        $this->assertSame('Internal Server Error', $response->faultString);
        $this->assertSame([], $response->errors);
    }

    public function testErrorsArrayIsEmptyWhenNoDetailElement(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <soapenv:Fault>
                    <faultcode>soapenv:Client</faultcode>
                    <faultstring>Bad Request</faultstring>
                </soapenv:Fault>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = ErrorResponse::fromXml($xml);

        $this->assertNotNull($response);
        $this->assertSame([], $response->errors);
        $this->assertNull($response->detail);
    }

    public function testFromXmlParsesErrorWithPartialFields(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <soapenv:Fault>
                    <faultcode>soapenv:Server</faultcode>
                    <faultstring>Error</faultstring>
                    <detail>
                        <err:Errors xmlns:err="http://ec.europa.eu/sanco/tracesnt/error/v01">
                            <err:Error>
                                <err:ID>ERR-003</err:ID>
                                <err:Message>Some error occurred</err:Message>
                            </err:Error>
                        </err:Errors>
                    </detail>
                </soapenv:Fault>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = ErrorResponse::fromXml($xml);

        $this->assertNotNull($response);
        $this->assertCount(1, $response->errors);
        $this->assertSame('ERR-003', $response->errors[0]->id);
        $this->assertSame('Some error occurred', $response->errors[0]->message);
        $this->assertNull($response->errors[0]->field);
    }
}
