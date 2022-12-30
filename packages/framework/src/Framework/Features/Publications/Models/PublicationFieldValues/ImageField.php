<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Publications\Models\PublicationFieldValues;

use Hyde\Framework\Features\Publications\PublicationFieldTypes;

final class ImageField extends PublicationFieldValue
{
    public const TYPE = PublicationFieldTypes::Image;

    protected static function parseInput(string $input): string
    {
        return $input;
    }
}
