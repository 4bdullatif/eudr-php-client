<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Support;

use Eudr\Enums\V3\EudrStatus;
use Eudr\Enums\V3\IdentifierType;
use Eudr\Enums\V3\OperatorRole;
use Eudr\Enums\V3\SdOperatorRole;
use Eudr\Exceptions\ValidationException;
use Eudr\Support\V3\Rules;
use Eudr\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class V3RulesTest extends TestCase
{
    /**
     * @return iterable<string, array{float, int, string}>
     */
    public static function decimalProvider(): iterable
    {
        yield 'integer value' => [200.0, 6, '200'];
        yield 'fraction' => [2.5, 6, '2.5'];
        yield 'tiny value' => [0.000001, 6, '0.000001'];
        yield 'rounds beyond precision' => [12.3456789, 6, '12.345679'];
        yield 'below precision becomes zero' => [1.0E-7, 6, '0'];
        yield 'negative zero' => [-1.0E-7, 6, '0'];
        yield 'large value' => [1.0E+9, 6, '1000000000'];
        yield 'float noise' => [0.1 + 0.2, 6, '0.3'];
        yield 'three digit precision' => [2.3456, 3, '2.346'];
        yield 'negative percentage' => [-1.5, 3, '-1.5'];
    }

    #[DataProvider('decimalProvider')]
    public function testDecimal(float $value, int $digits, string $expected): void
    {
        $this->assertSame($expected, Rules::decimal($value, $digits));
    }

    public function testEuropeanCountriesMatchXsdEnumeration(): void
    {
        $xsd = (string) file_get_contents(__DIR__ . '/../../Fixtures/xsd/v3/common.xsd');

        $this->assertSame(self::xsdEnumeration($xsd, 'EuropeanCountryType'), Rules::EUROPEAN_COUNTRIES);
    }

    public function testEnumsMatchXsdEnumerations(): void
    {
        $common = (string) file_get_contents(__DIR__ . '/../../Fixtures/xsd/v3/common.xsd');
        $sd = (string) file_get_contents(__DIR__ . '/../../Fixtures/xsd/v3/sd.xsd');

        $this->assertSame(
            self::xsdEnumeration($common, 'EudrStatusType'),
            array_map(static fn (EudrStatus $s) => $s->value, array_filter(EudrStatus::cases(), static fn (EudrStatus $s) => $s !== EudrStatus::UNKNOWN)),
        );
        $this->assertSame(
            self::xsdEnumeration($sd, 'SdOperatorRoleType'),
            array_map(static fn (SdOperatorRole $r) => $r->value, SdOperatorRole::cases()),
        );
        $this->assertSame(
            self::xsdEnumeration($common, 'IdentifierTypeType'),
            array_map(static fn (IdentifierType $t) => $t->value, IdentifierType::cases()),
        );
        $this->assertSame(
            self::xsdEnumeration((string) file_get_contents(__DIR__ . '/../../Fixtures/xsd/v3/dds.xsd'), 'OperatorRoleType'),
            array_map(static fn (OperatorRole $r) => $r->value, OperatorRole::cases()),
        );
    }

    /**
     * @return iterable<string, array{callable(): void}>
     */
    public static function trailingNewlineProvider(): iterable
    {
        yield 'uuid' => [static fn () => Rules::uuid("071874bd-8c62-4cac-8eb6-b2fbe003410c\n", 'UUID')];
        yield 'country code' => [static fn () => Rules::countryCode("DE\n", 'Country')];
        yield 'hs heading' => [static fn () => Rules::hsHeading("4401\n", 'HS')];
        yield 'email' => [static fn () => Rules::email("a@example.com\n", 'Email')];
    }

    /**
     * @param callable(): void $check
     */
    #[DataProvider('trailingNewlineProvider')]
    public function testPatternsRejectTrailingNewline(callable $check): void
    {
        $this->expectException(ValidationException::class);
        $check();
    }

    public function testBase64AcceptsWhitespaceAndRejectsGarbage(): void
    {
        Rules::base64("eyJ0eXBlIjoi\nUG9pbnQifQ==", 'Geo');
        $this->addToAssertionCount(1);

        $this->expectException(ValidationException::class);
        Rules::base64('{"type":"Point"}', 'Geo');
    }

    /**
     * @return list<string>
     */
    private static function xsdEnumeration(string $xsd, string $type): array
    {
        if (!preg_match('#<xs:simpleType name="' . $type . '">(.*?)</xs:simpleType>#s', $xsd, $m)) {
            return [];
        }

        preg_match_all('#<xs:enumeration value="([^"]+)"#', $m[1], $values);

        return $values[1];
    }
}
