<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Publications\Models\PublicationFields;

use Hyde\Framework\Features\Publications\PublicationFieldTypes;
use function is_array;

final class ArrayField extends PublicationField
{
    public const TYPE = PublicationFieldTypes::Array;

    public function __construct(string|array $value = null)
    {
        if (is_array($value)) {
            $this->value = $value;
        } else {
            parent::__construct($value);
        }
    }

    protected static function parseInput(string $input): array
    {
        return (array) $input;
    }
}
