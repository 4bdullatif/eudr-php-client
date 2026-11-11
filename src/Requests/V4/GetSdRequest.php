<?php

declare(strict_types=1);

namespace Eudr\Requests\V4;

use Eudr\Config\Config;
use Eudr\Exceptions\ValidationException;
use Eudr\Support\V4\Rules;
use Eudr\Support\Xml;

/**
 * getSd — retrieve the overview of one or more Simplified Declarations by UUID,
 * optionally for a specific version (V4).
 */
final class GetSdRequest extends AbstractV4Request
{
    public const MAX_UUIDS = 100;

    public const SOAP_ACTION = Xml::EUDR_SD_NS_V4 . '/getSd';

    /** @var list<array{uuid: string, version: ?int}> */
    private array $entries = [];

    private function __construct()
    {
    }

    public static function make(): static
    {
        return new self();
    }

    public function addUuid(string $uuid, ?int $version = null): static
    {
        $clone = clone $this;
        $clone->entries = [...$this->entries, ['uuid' => $uuid, 'version' => $version]];
        return $clone;
    }

    /**
     * @param list<string> $uuids Up to 100 UUIDs per call (latest versions).
     */
    public function withUuids(array $uuids): static
    {
        $clone = clone $this;
        $clone->entries = array_map(
            static fn (string $uuid): array => ['uuid' => $uuid, 'version' => null],
            $uuids,
        );
        return $clone;
    }

    public function getEndpoint(): string
    {
        return AbstractSdStatementRequest::ENDPOINT;
    }

    public function getSoapAction(): string
    {
        return self::SOAP_ACTION;
    }

    public function validate(): void
    {
        parent::validate();

        if ($this->entries === []) {
            throw new ValidationException('At least one SD UUID is required');
        }

        if (\count($this->entries) > self::MAX_UUIDS) {
            throw new ValidationException('Maximum ' . self::MAX_UUIDS . ' UUIDs per request');
        }

        foreach ($this->entries as $index => $entry) {
            Rules::uuid($entry['uuid'], "SD UUID #{$index}");

            if ($entry['version'] !== null && $entry['version'] < 1) {
                throw new ValidationException("SD version #{$index} must be a positive integer");
            }
        }
    }

    public function toSoapXml(Config $config): string
    {
        $builder = $this->createBuilder($config);
        $builder->addNamespace('sd', Xml::EUDR_SD_NS_V4);
        $builder->addNamespace('eudrCommon', Xml::EUDR_COMMON_NS_V4);

        $request = $builder->createElement('sd:GetSdRequest');
        $builder->getBody()->appendChild($request);

        foreach ($this->entries as $entry) {
            $entryEl = $builder->createElement('sd:uuidAndVersionNumberList');
            $request->appendChild($entryEl);

            $entryEl->appendChild($builder->createElement('eudrCommon:uuid', $entry['uuid']));

            if ($entry['version'] !== null) {
                $entryEl->appendChild($builder->createElement('eudrCommon:versionNumber', (string) $entry['version']));
            }
        }

        return $builder->toString();
    }
}
