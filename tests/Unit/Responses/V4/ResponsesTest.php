<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Responses\V4;

use Eudr\Enums\V4\EudrStatus;
use Eudr\Exceptions\XmlException;
use Eudr\Responses\ErrorResponse;
use Eudr\Responses\V4\ModificationResponse;
use Eudr\Responses\V4\OverviewResponse;
use Eudr\Responses\V4\SubmitDdsResponse;
use Eudr\Responses\V4\SubmitSdResponse;
use Eudr\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Parses the official V4 sample responses published in the EUDR operator API documentation.
 */
final class ResponsesTest extends TestCase
{
    public function testSubmitDdsResponse(): void
    {
        $response = SubmitDdsResponse::fromXml($this->fixture('v4/submit_dds_response.xml'));

        $this->assertTrue($response->isSuccess());
        $this->assertSame('071874bd-8c62-4cac-8eb6-b2fbe003410c', $response->uuid);
        $this->assertSame('Example EUDR announcement.', $response->userMessage);
    }

    public function testSubmitSdResponse(): void
    {
        $response = SubmitSdResponse::fromXml($this->fixture('v4/submit_sd_response.xml'));

        $this->assertTrue($response->isSuccess());
        $this->assertSame('e3b3206d-6625-47a0-b2ef-b8a7b9da1217', $response->sdIdentifier);
        $this->assertSame('Example EUDR announcement.', $response->userMessage);
    }

    /**
     * @return iterable<string, array{string, string, EudrStatus, ?int}>
     */
    public static function modificationProvider(): iterable
    {
        yield 'amendDds' => ['v4/amend_dds_response.xml', '071874bd-8c62-4cac-8eb6-b2fbe003410c', EudrStatus::AVAILABLE, null];
        yield 'withdrawDds' => ['v4/withdraw_dds_response.xml', '071874bd-8c62-4cac-8eb6-b2fbe003410c', EudrStatus::WITHDRAWN, null];
        yield 'updateSd' => ['v4/update_sd_response.xml', 'e3b3206d-6625-47a0-b2ef-b8a7b9da1217', EudrStatus::AVAILABLE, 2];
        yield 'withdrawSd' => ['v4/withdraw_sd_response.xml', 'e3b3206d-6625-47a0-b2ef-b8a7b9da1217', EudrStatus::WITHDRAWN, null];
    }

    #[DataProvider('modificationProvider')]
    public function testModificationResponses(string $fixture, string $uuid, EudrStatus $status, ?int $version): void
    {
        $response = ModificationResponse::fromXml($this->fixture($fixture));

        $this->assertTrue($response->isSuccess());
        $this->assertSame($uuid, $response->uuid);
        $this->assertSame($status, $response->status);
        $this->assertSame($status->value, $response->rawStatusValue);
        $this->assertSame($version, $response->version);
        $this->assertNull($response->userMessage);
    }

    /**
     * @return iterable<string, array{string, string, string, string, string, ?int}>
     */
    public static function overviewProvider(): iterable
    {
        yield 'getDds' => ['v4/get_dds_response.xml', '071874bd-8c62-4cac-8eb6-b2fbe003410c', '26BEDWNW9JD1TN', '26BE7XTVCZAQ2S', 'SFFCB4Y3', 1];
        yield 'getDdsByInternalReference' => ['v4/get_dds_by_internal_reference_response.xml', '071874bd-8c62-4cac-8eb6-b2fbe003410c', '26BEDWNW9JD1TN', '26BE7XTVCZAQ2S', 'SFFCB4Y3', 1];
        yield 'getSd' => ['v4/get_sd_response.xml', 'e3b3206d-6625-47a0-b2ef-b8a7b9da1217', 'REF-0001000087', 'S26BECB39D2GRX', 'H6ORMNTX', null];
        yield 'getSdByInternalReference' => ['v4/get_sd_by_internal_reference_response.xml', 'e3b3206d-6625-47a0-b2ef-b8a7b9da1217', 'REF-0001000087', 'S26BECB39D2GRX', 'H6ORMNTX', null];
    }

    #[DataProvider('overviewProvider')]
    public function testOverviewResponses(
        string $fixture,
        string $uuid,
        string $internalReference,
        string $referenceNumber,
        string $verificationNumber,
        ?int $version,
    ): void {
        $all = OverviewResponse::allFromXml($this->fixture($fixture));
        $this->assertCount(1, $all);

        $overview = OverviewResponse::fromXml($this->fixture($fixture));

        $this->assertEquals($all[0], $overview);
        $this->assertTrue($overview->isSuccess());
        $this->assertSame($uuid, $overview->uuid);
        $this->assertSame($internalReference, $overview->internalReferenceNumber);
        $this->assertSame($referenceNumber, $overview->referenceNumber);
        $this->assertSame($verificationNumber, $overview->verificationNumber);
        $this->assertSame(EudrStatus::AVAILABLE, $overview->status);
        $this->assertNotNull($overview->date);
        $this->assertNotNull($overview->updatedBy);
        $this->assertNull($overview->rejectionReason);
        $this->assertNull($overview->communicationToOperatorDate);
        $this->assertSame($version, $overview->version);
        $this->assertSame('Assigned', $overview->referenceNumberInfo);
        $this->assertNull($overview->userMessage);
    }

    public function testUserMessageIsPropagatedToEveryOverview(): void
    {
        $xml = str_replace(
            '<S:Body>',
            '<S:Header><eudr:EudrUserMessage xmlns:eudr="http://ec.europa.eu/tracesnt/certificate/eudr/common/v4">Planned maintenance</eudr:EudrUserMessage></S:Header><S:Body>',
            $this->fixture('v4/get_dds_response_multiple.xml'),
        );

        $all = OverviewResponse::allFromXml($xml);

        $this->assertCount(2, $all);
        $this->assertSame('Planned maintenance', $all[0]->userMessage);
        $this->assertSame('Planned maintenance', $all[1]->userMessage);
        $this->assertSame('Planned maintenance', OverviewResponse::fromXml($xml)->userMessage);
    }

    public function testCancelledStatusIsRecognised(): void
    {
        $xml = str_replace('<ns5:status>WITHDRAWN</ns5:status>', '<ns5:status>CANCELLED</ns5:status>', $this->fixture('v4/withdraw_dds_response.xml'));

        $this->assertSame(EudrStatus::CANCELLED, ModificationResponse::fromXml($xml)->status);
    }

    public function testOverviewWithAllFieldsAndUnknownStatus(): void
    {
        $all = OverviewResponse::allFromXml($this->fixture('v4/get_dds_response_multiple.xml'));

        $this->assertCount(2, $all);

        $rejected = $all[0];
        $this->assertSame(EudrStatus::REJECTED, $rejected->status);
        $this->assertNull($rejected->referenceNumber);
        $this->assertNull($rejected->verificationNumber);
        $this->assertSame('Geolocation outside country of production', $rejected->rejectionReason);
        $this->assertSame('2026-05-21T08:00:00.000Z', $rejected->communicationToOperatorDate);
        $this->assertSame('Please correct the plot coordinates.', $rejected->communicationToOperatorContent);
        $this->assertSame('2026-05-20T09:55:01.000Z', $rejected->date);
        $this->assertSame(2, $rejected->version);

        $unknown = $all[1];
        $this->assertSame(EudrStatus::UNKNOWN, $unknown->status);
        $this->assertSame('SOME_FUTURE_STATUS', $unknown->rawStatusValue);
        $this->assertNull($unknown->date);
        $this->assertNull($unknown->version);
        $this->assertNull($unknown->referenceNumberInfo);
    }

    public function testEmptyOverviewList(): void
    {
        $this->assertSame([], OverviewResponse::allFromXml($this->fixture('v4/get_dds_response_empty.xml')));

        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('contains no declaration overview');
        OverviewResponse::fromXml($this->fixture('v4/get_dds_response_empty.xml'));
    }

    public function testMissingResponseElementThrows(): void
    {
        $xml = $this->fixture('submit_response_v2.xml');

        foreach ([
            static fn () => SubmitDdsResponse::fromXml($xml),
            static fn () => SubmitSdResponse::fromXml($xml),
            static fn () => ModificationResponse::fromXml($xml),
            static fn () => OverviewResponse::allFromXml($xml),
        ] as $parse) {
            try {
                $parse();
                $this->fail('Expected XmlException');
            } catch (XmlException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function notFoundProvider(): iterable
    {
        yield 'dds' => ['v4/dds_not_found_fault.xml', 'Statement detail access is possible only over UI'];
        yield 'sd' => ['v4/sd_not_found_fault.xml', 'Declaration detail access is possible only over UI'];
    }

    #[DataProvider('notFoundProvider')]
    public function testNotFoundFaultIsParsed(string $fixture, string $message): void
    {
        $error = ErrorResponse::fromXml($this->fixture($fixture));

        $this->assertNotNull($error);
        $this->assertSame('S:Server', $error->faultCode);
        $this->assertSame($message, $error->faultString);
    }

    public function testBusinessRulesFaultDetailsAreParsed(): void
    {
        $error = ErrorResponse::fromXml($this->fixture('v4/business_rules_fault.xml'));

        $this->assertNotNull($error);
        $this->assertCount(1, $error->errors);
        $this->assertSame('EUDR-GROUPING-NOT-ALLOWED-ON-AMEND', $error->errors[0]->id);
        $this->assertSame('groupedDeclarations', $error->errors[0]->field);
    }
}
