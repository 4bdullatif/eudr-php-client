<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Responses;

use Eudr\Responses\ErrorResponse;
use Eudr\Tests\TestCase;

final class ErrorResponseTest extends TestCase
{
    public function testFromXmlParsesSoapFault(): void
    {
        $xml = $this->fixture('soap_fault.xml');
        $response = ErrorResponse::fromXml($xml);

        $this->assertNotNull($response);
        $this->assertSame('soapenv:Server', $response->faultCode);
        $this->assertSame('Internal Server Error', $response->faultString);
        $this->assertNotNull($response->detail);
        $this->assertStringContainsString('Something went wrong', $response->detail);
    }

    public function testIsSuccessAlwaysReturnsFalse(): void
    {
        $response = new ErrorResponse('code', 'message');
        $this->assertFalse($response->isSuccess());
    }

    public function testFromXmlReturnsNullForNonFaultResponse(): void
    {
        $xml = $this->fixture('submit_response_v2.xml');
        $response = ErrorResponse::fromXml($xml);
        $this->assertNull($response);
    }
}
