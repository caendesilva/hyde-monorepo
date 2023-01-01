<?php /** @noinspection PhpDuplicateMatchArmBodyInspection */

declare(strict_types=1);

namespace Hyde\Framework\Features\Publications;

use Hyde\Framework\Features\Publications\Models\PublicationFieldDefinition;
use Hyde\Framework\Features\Publications\Models\PublicationFields\ArrayField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\BooleanField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\DatetimeField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\FloatField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\ImageField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\IntegerField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\StringField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\TagField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\TextField;
use Hyde\Framework\Features\Publications\Models\PublicationFields\UrlField;
use Hyde\Framework\Features\Publications\Models\PublicationType;
use Hyde\Framework\Features\Publications\Validation\BooleanRule;

use function array_merge;
use function collect;

/**
 * @see  \Hyde\Framework\Features\Publications\Models\PublicationFields\StringField
 * @see  \Hyde\Framework\Features\Publications\Models\PublicationFields\DatetimeField
 * @see  \Hyde\Framework\Features\Publications\Models\PublicationFields\BooleanField
 * @see  \Hyde\Framework\Features\Publications\Models\PublicationFields\IntegerField
 * @see  \Hyde\Framework\Features\Publications\Models\PublicationFields\FloatField
 * @see  \Hyde\Framework\Features\Publications\Models\PublicationFields\ImageField
 * @see  \Hyde\Framework\Features\Publications\Models\PublicationFields\ArrayField
 * @see  \Hyde\Framework\Features\Publications\Models\PublicationFields\TextField
 * @see  \Hyde\Framework\Features\Publications\Models\PublicationFields\UrlField
 * @see  \Hyde\Framework\Features\Publications\Models\PublicationFields\TagField
 */
class PublicationFieldService
{
    public static function normalizeFieldValue(PublicationFieldTypes $fieldType, mixed $value)
    {
        if ($fieldType == PublicationFieldTypes::String) {
            return $value;
        }

        if ($fieldType == PublicationFieldTypes::Datetime) {
            return $value;
        }

        if ($fieldType == PublicationFieldTypes::Boolean) {
            return $value;
        }

        if ($fieldType == PublicationFieldTypes::Integer) {
            return $value;
        }

        if ($fieldType == PublicationFieldTypes::Float) {
            return $value;
        }

        if ($fieldType == PublicationFieldTypes::Image) {
            return $value;
        }

        if ($fieldType == PublicationFieldTypes::Array) {
            return $value;
        }

        if ($fieldType == PublicationFieldTypes::Text) {
            return $value;
        }

        if ($fieldType == PublicationFieldTypes::Url) {
            return $value;
        }

        if ($fieldType == PublicationFieldTypes::Tag) {
            return $value;
        }
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
}
