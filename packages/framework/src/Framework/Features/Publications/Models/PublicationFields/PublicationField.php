<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Publications\Models\PublicationFields;

use Hyde\Framework\Features\Publications\Models\PublicationFieldDefinition;
use Hyde\Framework\Features\Publications\PublicationFieldService;
use Hyde\Framework\Features\Publications\PublicationFieldTypes;
use RuntimeException;

/**
 * Represents a single value for a field in a publication,
 * as defined in the "fields" array of a publication type schema.
 *
 * @see \Hyde\Framework\Features\Publications\PublicationFieldTypes
 * @see \Hyde\Framework\Testing\Feature\PublicationFieldServiceTest
 */
abstract class PublicationField
{
    /** @var \Hyde\Framework\Features\Publications\PublicationFieldTypes */
    public const TYPE = null;

    protected mixed $value;

    public function __construct(string $value = null)
    {
        if ($value !== null) {
            $this->value = static::parseInput($value);
        }
    }

    final public function getValue(): mixed
    {
        return $this->value;
    }

    final public static function getType(): PublicationFieldTypes
    {
        return static::TYPE ?? throw new RuntimeException('PublicationField::TYPE must be set in child class.');
    }

    /**
     * Parse an input string from the command line into a value with the appropriate type for this field.
     *
     * @param  string  $input
     * @return mixed
     */
    final public static function parseInput(string $input): mixed
    {
        return PublicationFieldService::parseFieldValue(static::getType(), $input);
    }

    /**
     * Get the validation rules that apply to the field.
     *
     * @param  \Hyde\Framework\Features\Publications\Models\PublicationFieldDefinition|null  $fieldDefinition
     * @return array<string>
     */
    public function getRules(?PublicationFieldDefinition $fieldDefinition = null): array
    {
        return $fieldDefinition?->getValidationRules() ?? static::rules();
    }

    /**
     * Get the default validation rules for this field type.
     *
     * @return array<string>
     */
    final public static function rules(): array
    {
        return PublicationFieldService::getDefaultValidationRulesForFieldType(static::getType());
    }
}
