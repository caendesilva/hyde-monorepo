<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Publications\Models\PublicationFields;

use Hyde\Framework\Features\Publications\Models\PublicationFields\Concerns\CanonicableTrait;
use Hyde\Framework\Features\Publications\Models\PublicationFields\Contracts\Canonicable;
use Hyde\Framework\Features\Publications\PublicationFieldTypes;

final class IntegerField extends PublicationField implements Canonicable
{
    use CanonicableTrait;

    public const TYPE = PublicationFieldTypes::Integer;

    protected static function parseInput(string $input): int
    {
        if (! is_numeric($input)) {
            throw self::parseError($input);
        }

        return (int) $input;
    }

    public static function rules(): array
    {
        return ['integer', 'numeric'];
    }
}
