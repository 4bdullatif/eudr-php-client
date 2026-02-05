<?php

declare(strict_types=1);

namespace Eudr\Requests\V1\Concerns;

use Eudr\Data\Commodity;
use Eudr\Data\EconomicOperator;
use Eudr\Data\Producer;
use Eudr\Enums\ActivityType;
use Eudr\Support\XmlBuilder;

/**
 * Shared XML building logic for V1 submit/amend statement bodies.
 */
trait BuildsV1StatementXml
{
    /**
     * @param list<Commodity> $commodities
     * @param list<array{referenceNumber: string, verificationNumber: string}> $associatedStatements
     */
    private function buildStatementChildren(
        XmlBuilder $builder,
        \DOMElement $statement,
        ?string $internalReference,
        ?ActivityType $activityType,
        ?EconomicOperator $operator,
        ?string $countryOfActivity,
        ?string $borderCrossCountry,
        ?string $comment,
        array $commodities,
        bool $geoLocationConfidential,
        array $associatedStatements,
    ): void {
        if ($internalReference !== null) {
            $refEl = $builder->createElement('v11:internalReferenceNumber', $internalReference);
            $statement->appendChild($refEl);
        }

        if ($activityType !== null) {
            $actEl = $builder->createElement('v11:activityType', $activityType->value);
            $statement->appendChild($actEl);
        }

        if ($operator !== null) {
            $this->addV1OperatorXml($builder, $statement, $operator);
        }

        if ($countryOfActivity !== null) {
            $countryEl = $builder->createElement('v11:countryOfActivity', $countryOfActivity);
            $statement->appendChild($countryEl);
        }

        if ($borderCrossCountry !== null) {
            $borderEl = $builder->createElement('v11:borderCrossCountry', $borderCrossCountry);
            $statement->appendChild($borderEl);
        }

        if ($comment !== null) {
            $commentEl = $builder->createElement('v11:comment', $comment);
            $statement->appendChild($commentEl);
        }

        foreach ($commodities as $commodity) {
            $this->addV1CommodityXml($builder, $statement, $commodity);
        }

        $geoConfEl = $builder->createElement(
            'v11:geoLocationConfidential',
            $geoLocationConfidential ? 'true' : 'false',
        );
        $statement->appendChild($geoConfEl);

        foreach ($associatedStatements as $assoc) {
            $assocEl = $builder->createElement('v11:associatedStatements');
            $statement->appendChild($assocEl);

            $assocRefEl = $builder->createElement('v11:referenceNumber', $assoc['referenceNumber']);
            $assocEl->appendChild($assocRefEl);

            $assocVerEl = $builder->createElement('v11:verificationNumber', $assoc['verificationNumber']);
            $assocEl->appendChild($assocVerEl);
        }
    }

    private function addV1OperatorXml(XmlBuilder $builder, \DOMElement $parent, EconomicOperator $operator): void
    {
        $opEl = $builder->createElement('v11:operator');
        $parent->appendChild($opEl);

        foreach ($operator->referenceNumbers as $refNum) {
            $refNumEl = $builder->createElement('v11:referenceNumber');
            $opEl->appendChild($refNumEl);

            $idTypeEl = $builder->createElement('v11:identifierType', $refNum['identifierType']);
            $refNumEl->appendChild($idTypeEl);

            $idValueEl = $builder->createElement('v11:identifierValue', $refNum['identifierValue']);
            $refNumEl->appendChild($idValueEl);
        }

        $nameAddress = $builder->createElement('v11:nameAndAddress');
        $opEl->appendChild($nameAddress);

        if ($operator->name !== null) {
            $nameEl = $builder->createElement('v4:name', $operator->name);
            $nameAddress->appendChild($nameEl);
        }

        if ($operator->address !== null) {
            $address = $operator->address;

            if ($address->countryCode !== null) {
                $countryEl = $builder->createElement('v4:country', $address->countryCode);
                $nameAddress->appendChild($countryEl);
            }

            $addressParts = array_filter([
                $address->street !== null && $address->number !== null ? trim($address->street . ' ' . $address->number) : ($address->street ?? $address->number),
                $address->postcode,
                $address->city,
            ]);

            $flatAddress = implode(', ', array_map('trim', $addressParts));

            if ($flatAddress !== '') {
                $addressEl = $builder->createElement('v4:address', $flatAddress);
                $nameAddress->appendChild($addressEl);
            }
        }

        if ($operator->email !== null) {
            $emailEl = $builder->createElement('v11:email', $operator->email);
            $opEl->appendChild($emailEl);
        }

        if ($operator->phone !== null) {
            $phoneEl = $builder->createElement('v11:phone', $operator->phone);
            $opEl->appendChild($phoneEl);
        }
    }

    private function addV1CommodityXml(XmlBuilder $builder, \DOMElement $parent, Commodity $commodity): void
    {
        $commEl = $builder->createElement('v11:commodities');
        $parent->appendChild($commEl);

        $posEl = $builder->createElement('v11:position', (string) $commodity->position);
        $commEl->appendChild($posEl);

        $descriptorsEl = $builder->createElement('v11:descriptors');
        $commEl->appendChild($descriptorsEl);

        $descEl = $builder->createElement('v11:descriptionOfGoods', $commodity->description);
        $descriptorsEl->appendChild($descEl);

        $hasMeasure = $commodity->volume !== null
            || $commodity->netWeight !== null
            || $commodity->numberOfUnits !== null
            || $commodity->supplementaryUnit !== null;

        if ($hasMeasure) {
            $measureEl = $builder->createElement('v11:goodsMeasure');
            $descriptorsEl->appendChild($measureEl);

            if ($commodity->volume !== null) {
                $volEl = $builder->createElement('v11:volume', (string) $commodity->volume);
                $measureEl->appendChild($volEl);
            }

            if ($commodity->netWeight !== null) {
                $weightEl = $builder->createElement('v11:netWeight', (string) $commodity->netWeight);
                $measureEl->appendChild($weightEl);
            }

            if ($commodity->numberOfUnits !== null) {
                $unitsEl = $builder->createElement('v11:numberOfUnits', (string) $commodity->numberOfUnits);
                $measureEl->appendChild($unitsEl);
            }

            if ($commodity->supplementaryUnit !== null) {
                $suppEl = $builder->createElement('v11:supplementaryUnit', $commodity->supplementaryUnit);
                $measureEl->appendChild($suppEl);
            }

            if ($commodity->supplementaryUnitQualifier !== null) {
                $qualEl = $builder->createElement(
                    'v11:supplementaryUnitQualifier',
                    $commodity->supplementaryUnitQualifier,
                );
                $measureEl->appendChild($qualEl);
            }
        }

        $hsEl = $builder->createElement('v11:hsHeading', $commodity->hsHeading);
        $commEl->appendChild($hsEl);

        foreach ($commodity->speciesInfo as $species) {
            $specEl = $builder->createElement('v11:speciesInfo');
            $commEl->appendChild($specEl);

            if ($species->scientificName !== null) {
                $sciEl = $builder->createElement('v11:scientificName', $species->scientificName);
                $specEl->appendChild($sciEl);
            }

            if ($species->commonName !== null) {
                $comEl = $builder->createElement('v11:commonName', $species->commonName);
                $specEl->appendChild($comEl);
            }
        }

        foreach ($commodity->producers as $producer) {
            $this->addV1ProducerXml($builder, $commEl, $producer);
        }
    }

    private function addV1ProducerXml(XmlBuilder $builder, \DOMElement $parent, Producer $producer): void
    {
        $prodEl = $builder->createElement('v11:producers');
        $parent->appendChild($prodEl);

        $countryEl = $builder->createElement('v11:country', $producer->country);
        $prodEl->appendChild($countryEl);

        if ($producer->name !== null) {
            $nameEl = $builder->createElement('v11:name', $producer->name);
            $prodEl->appendChild($nameEl);
        }

        $geoEl = $builder->createElement('v11:geometryGeojson', $producer->geometryGeojson);
        $prodEl->appendChild($geoEl);
    }
}
