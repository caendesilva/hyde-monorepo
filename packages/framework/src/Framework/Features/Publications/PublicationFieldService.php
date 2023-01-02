<?php

/** @noinspection PhpDuplicateMatchArmBodyInspection */

declare(strict_types=1);

namespace Hyde\Framework\Features\Publications;

use function array_merge;
use function collect;
use DateTime;
use function filter_var;
use Hyde\Framework\Features\Publications\Models\PublicationFieldDefinition;
use Hyde\Framework\Features\Publications\Models\PublicationType;
use Hyde\Framework\Features\Publications\Validation\BooleanRule;
use InvalidArgumentException;
use function is_numeric;

/**
 * @see \Hyde\Framework\Testing\Feature\PublicationFieldServiceTest
 */
class PublicationFieldService
{
    public static function parseFieldValue(PublicationFieldTypes $fieldType, string|array $value): float|DateTime|array|bool|int|string
    {
        return match ($fieldType) {
            PublicationFieldTypes::String => self::parseStringValue($value),
            PublicationFieldTypes::Datetime => self::parseDatetimeValue($value),
            PublicationFieldTypes::Boolean => self::parseBooleanValue($value),
            PublicationFieldTypes::Integer => self::parseIntegerValue($value),
            PublicationFieldTypes::Float => self::parseFloatValue($value),
            PublicationFieldTypes::Image => self::parseImageValue($value),
            PublicationFieldTypes::Array => self::parseArrayValue($value),
            PublicationFieldTypes::Text => self::parseTextValue($value),
            PublicationFieldTypes::Url => self::parseUrlValue($value),
            PublicationFieldTypes::Tag => self::parseTagValue($value),
        };
    }

    public static function getDefaultValidationRulesForFieldType(PublicationFieldTypes $fieldType): array
    {
        return match ($fieldType) {
            PublicationFieldTypes::String => ['string'],
            PublicationFieldTypes::Datetime => ['date'],
            PublicationFieldTypes::Boolean => [new BooleanRule],
            PublicationFieldTypes::Integer => ['integer', 'numeric'],
            PublicationFieldTypes::Float => ['numeric'],
            PublicationFieldTypes::Image => [],
            PublicationFieldTypes::Array => ['array'],
            PublicationFieldTypes::Text => ['string'],
            PublicationFieldTypes::Url => ['url'],
            PublicationFieldTypes::Tag => [],
        };
    }

    public static function getValidationRulesForPublicationFieldEntry(PublicationType $publicationType, string $fieldName): array
    {
        return self::getValidationRulesForPublicationFieldDefinition($publicationType,
            $publicationType->getFieldDefinition($fieldName)
        );
    }

    public static function getValidationRulesForPublicationFieldDefinition(?PublicationType $publicationType, PublicationFieldDefinition $fieldDefinition): array
    {
        return array_merge(
            self::getDefaultValidationRulesForFieldType($fieldDefinition->type),
            self::makeDynamicValidationRulesForPublicationFieldEntry($fieldDefinition, $publicationType),
            $fieldDefinition->rules
        );
    }

    protected static function makeDynamicValidationRulesForPublicationFieldEntry(
        Models\PublicationFieldDefinition $fieldDefinition, ?PublicationType $publicationType
    ): array {
        if ($fieldDefinition->type == PublicationFieldTypes::Image) {
            if ($publicationType !== null) {
                $mediaFiles = PublicationService::getMediaForPubType($publicationType);
                $valueList = $mediaFiles->implode(',');
            } else {
                $valueList = '';
            }

            return ["in:$valueList"];
        }

        if ($fieldDefinition->type == PublicationFieldTypes::Tag) {
            if ($publicationType !== null) {
                $tagValues = PublicationService::getValuesForTagName($publicationType->getIdentifier()) ?? collect([]);
                $valueList = $tagValues->implode(',');
            } else {
                $valueList = '';
            }

            return ["in:$valueList"];
        }

        return [];
    }

    protected static function parseError(string $typeName, string $input): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf("%s: Unable to parse invalid %s value '%s'",
            (ucfirst($typeName).'Field'), $typeName, $input
        ));
    }

    public static function parseStringValue(string $value): string
    {
        return $value;
    }

    public static function parseDatetimeValue(string $value): DateTime
    {
        return new DateTime($value);
    }

    public static function parseBooleanValue(string $value): bool
    {
        return match ($value) {
            'true', '1' => true,
            'false', '0' => false,
            default => throw self::parseError('boolean', $value)
        };
    }

    public static function parseIntegerValue(string $value): int
    {
        if (! is_numeric($value)) {
            throw self::parseError('integer', $value);
        }

        return (int) $value;
    }

    public static function parseFloatValue(string $value): float
    {
        if (! is_numeric($value)) {
            throw self::parseError('float', $value);
        }

        return (float) $value;
    }

    public static function parseImageValue(string $value): string
    {
        // TODO Validate file exists as the dynamic validation rules does the same
        return $value;
    }

    public static function parseArrayValue(string|array $value): array
    {
        return (array) $value;
    }

    public static function parseTextValue(string $value): string
    {
        // In order to properly store multi-line text fields as block literals,
        // we need to make sure the string ends with a newline character.

        if (substr_count($value, "\n") > 0) {
            return trim($value, "\r\n")."\n";
        }

        return $value;
    }

    public static function parseUrlValue(string $value): string
    {
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            throw self::parseError('url', $value);
        }

        return $value;
    }

    public static function parseTagValue(string|array $value): array
    {
        return (array) $value;
    }
}
