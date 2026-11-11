<?php

declare(strict_types=1);

namespace Eudr\Responses\V3\Concerns;

/**
 * Namespace-tolerant element lookup for V3 responses.
 *
 * Elements are matched by local name so that responses still parse if the server
 * uses different prefixes or (unexpectedly) a different namespace.
 *
 * @internal
 */
trait ReadsV3Xml
{
    /**
     * Find the first direct child of the SOAP Body with one of the given local names.
     *
     * @param list<string> $localNames
     */
    private static function findResponseElement(\SimpleXMLElement $body, array $localNames): ?\SimpleXMLElement
    {
        foreach ($localNames as $localName) {
            $found = self::childElements($body, $localName);
            if ($found !== []) {
                return $found[0];
            }
        }

        return null;
    }

    /**
     * @return list<\SimpleXMLElement>
     */
    private static function childElements(\SimpleXMLElement $parent, string $localName): array
    {
        $result = $parent->xpath('./*[local-name()="' . $localName . '"]');

        if (!\is_array($result)) {
            return [];
        }

        return array_values($result);
    }

    private static function childText(\SimpleXMLElement $parent, string $localName): ?string
    {
        $found = self::childElements($parent, $localName);

        if ($found === []) {
            return null;
        }

        return trim((string) $found[0]);
    }

    private static function childNonEmptyText(\SimpleXMLElement $parent, string $localName): ?string
    {
        $text = self::childText($parent, $localName);

        return $text === '' ? null : $text;
    }

    private static function childInt(\SimpleXMLElement $parent, string $localName): ?int
    {
        $text = self::childText($parent, $localName);

        if ($text === null || !preg_match('/^[+-]?\d+$/', $text)) {
            return null;
        }

        return (int) $text;
    }
}
