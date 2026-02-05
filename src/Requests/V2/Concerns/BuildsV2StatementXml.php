<?php

declare(strict_types=1);

namespace Eudr\Requests\V2\Concerns;

use Eudr\Data\Commodity;
use Eudr\Data\EconomicOperator;
use Eudr\Data\Producer;
use Eudr\Enums\ActivityType;
use Eudr\Support\XmlBuilder;

/**
 * Shared XML building logic for V2 submit/amend statement bodies.
 */
trait BuildsV2StatementXml
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
            $refEl = $builder->createElement('v21:internalReferenceNumber', $internalReference);
            $statement->appendChild($refEl);
        }

        if ($activityType !== null) {
            $actEl = $builder->createElement('v21:activityType', $activityType->value);
            $statement->appendChild($actEl);
        }

        if ($operator !== null) {
            $this->addV2OperatorXml($builder, $statement, $operator);
        }

        if ($countryOfActivity !== null) {
            $countryEl = $builder->createElement('v21:countryOfActivity', $countryOfActivity);
            $statement->appendChild($countryEl);
        }

        if ($borderCrossCountry !== null) {
            $borderEl = $builder->createElement('v21:borderCrossCountry', $borderCrossCountry);
            $statement->appendChild($borderEl);
        }

        if ($comment !== null) {
            $commentEl = $builder->createElement('v21:comment', $comment);
            $statement->appendChild($commentEl);
        }

        foreach ($commodities as $commodity) {
            $this->addV2CommodityXml($builder, $statement, $commodity);
        }

        $geoConfEl = $builder->createElement(
            'v21:geoLocationConfidential',
            $geoLocationConfidential ? 'true' : 'false',
        );
        $statement->appendChild($geoConfEl);

        foreach ($associatedStatements as $assoc) {
            $assocEl = $builder->createElement('v21:associatedStatements');
            $statement->appendChild($assocEl);

            $assocRefEl = $builder->createElement('v21:referenceNumber', $assoc['referenceNumber']);
            $assocEl->appendChild($assocRefEl);

            $assocVerEl = $builder->createElement('v21:verificationNumber', $assoc['verificationNumber']);
            $assocEl->appendChild($assocVerEl);
        }
    }

    private function addV2OperatorXml(XmlBuilder $builder, \DOMElement $parent, EconomicOperator $operator): void
    {
        $opEl = $builder->createElement('v21:operator');
        $parent->appendChild($opEl);

        foreach ($operator->referenceNumbers as $refNum) {
            $refNumEl = $builder->createElement('v21:referenceNumber');
            $opEl->appendChild($refNumEl);

            $idTypeEl = $builder->createElement('v21:identifierType', $refNum['identifierType']);
            $refNumEl->appendChild($idTypeEl);

            $idValueEl = $builder->createElement('v21:identifierValue', $refNum['identifierValue']);
            $refNumEl->appendChild($idValueEl);
        }

        $operatorAddress = $builder->createElement('v21:operatorAddress');
        $opEl->appendChild($operatorAddress);

        if ($operator->name !== null) {
            $nameEl = $builder->createElement('v21:name', $operator->name);
            $operatorAddress->appendChild($nameEl);
        }

        if ($operator->address !== null) {
            $address = $operator->address;

            if ($address->countryCode !== null) {
                $countryEl = $builder->createElement('v21:country', $address->countryCode);
                $operatorAddress->appendChild($countryEl);
            }

            $street = $address->street;
            if ($address->number !== null && $street !== null) {
                $street = $street . ' ' . $address->number;
            } elseif ($address->number !== null) {
                $street = $address->number;
            }

            if ($street !== null) {
                $streetEl = $builder->createElement('v21:street', $street);
                $operatorAddress->appendChild($streetEl);
            }

            if ($address->postcode !== null) {
                $postcodeEl = $builder->createElement('v21:postalCode', $address->postcode);
                $operatorAddress->appendChild($postcodeEl);
            }

            if ($address->city !== null) {
                $cityEl = $builder->createElement('v21:city', $address->city);
                $operatorAddress->appendChild($cityEl);
            }

            if ($address->fullAddress !== null) {
                $fullAddrEl = $builder->createElement('v21:fullAddress', $address->fullAddress);
                $operatorAddress->appendChild($fullAddrEl);
            }
        }

        if ($operator->email !== null) {
            $emailEl = $builder->createElement('v21:email', $operator->email);
            $opEl->appendChild($emailEl);
        }

        if ($operator->phone !== null) {
            $phoneEl = $builder->createElement('v21:phone', $operator->phone);
            $opEl->appendChild($phoneEl);
        }
    }

    private function addV2CommodityXml(XmlBuilder $builder, \DOMElement $parent, Commodity $commodity): void
    {
        $commEl = $builder->createElement('v21:commodities');
        $parent->appendChild($commEl);

        $posEl = $builder->createElement('v21:position', (string) $commodity->position);
        $commEl->appendChild($posEl);

        $descriptorsEl = $builder->createElement('v21:descriptors');
        $commEl->appendChild($descriptorsEl);

        $descEl = $builder->createElement('v21:descriptionOfGoods', $commodity->description);
        $descriptorsEl->appendChild($descEl);

        $hasMeasure = $commodity->percentageEstimationOrDeviation !== null
            || $commodity->volume !== null
            || $commodity->netWeight !== null
            || $commodity->numberOfUnits !== null
            || $commodity->supplementaryUnit !== null;

        if ($hasMeasure) {
            $measureEl = $builder->createElement('v21:goodsMeasure');
            $descriptorsEl->appendChild($measureEl);

            if ($commodity->percentageEstimationOrDeviation !== null) {
                $pctEl = $builder->createElement(
                    'v21:percentageEstimationOrDeviation',
                    (string) $commodity->percentageEstimationOrDeviation,
                );
                $measureEl->appendChild($pctEl);
            }

            if ($commodity->volume !== null) {
                $volEl = $builder->createElement('v21:volume', (string) $commodity->volume);
                $measureEl->appendChild($volEl);
            }

            if ($commodity->netWeight !== null) {
                $weightEl = $builder->createElement('v21:netWeight', (string) $commodity->netWeight);
                $measureEl->appendChild($weightEl);
            }

            if ($commodity->numberOfUnits !== null) {
                $unitsEl = $builder->createElement('v21:numberOfUnits', (string) $commodity->numberOfUnits);
                $measureEl->appendChild($unitsEl);
            }

            if ($commodity->supplementaryUnit !== null) {
                $suppEl = $builder->createElement('v21:supplementaryUnit', $commodity->supplementaryUnit);
                $measureEl->appendChild($suppEl);
            }

            if ($commodity->supplementaryUnitQualifier !== null) {
                $qualEl = $builder->createElement(
                    'v21:supplementaryUnitQualifier',
                    $commodity->supplementaryUnitQualifier,
                );
                $measureEl->appendChild($qualEl);
            }
        }

        $hsEl = $builder->createElement('v21:hsHeading', $commodity->hsHeading);
        $commEl->appendChild($hsEl);

        foreach ($commodity->speciesInfo as $species) {
            $specEl = $builder->createElement('v21:speciesInfo');
            $commEl->appendChild($specEl);

            if ($species->scientificName !== null) {
                $sciEl = $builder->createElement('v21:scientificName', $species->scientificName);
                $specEl->appendChild($sciEl);
            }

            if ($species->commonName !== null) {
                $comEl = $builder->createElement('v21:commonName', $species->commonName);
                $specEl->appendChild($comEl);
            }
        }

        foreach ($commodity->producers as $producer) {
            $this->addV2ProducerXml($builder, $commEl, $producer);
        }
    }

    private function addV2ProducerXml(XmlBuilder $builder, \DOMElement $parent, Producer $producer): void
    {
        $prodEl = $builder->createElement('v21:producers');
        $parent->appendChild($prodEl);

        $countryEl = $builder->createElement('v21:country', $producer->country);
        $prodEl->appendChild($countryEl);

        if ($producer->name !== null) {
            $nameEl = $builder->createElement('v21:name', $producer->name);
            $prodEl->appendChild($nameEl);
        }

        $geoEl = $builder->createElement('v21:geometryGeojson', $producer->geometryGeojson);
        $prodEl->appendChild($geoEl);
    }
}
