<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Publications\Models;

use Hyde\Support\Concerns\JsonSerializesArrayable;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * @see \Hyde\Framework\Testing\Feature\PublicationFieldTest
 */
class PublicationField implements JsonSerializable, Arrayable
{
    use JsonSerializesArrayable;

    public final const TYPES = ['string', 'boolean', 'integer', 'float', 'datetime', 'url', 'array', 'text', 'image'];
    public final const TYPE_LABELS = ['String', 'Boolean', 'Integer', 'Float', 'Datetime', 'URL', 'Array', 'Text', 'Local Image'];

    public function __construct(public readonly string $type, public readonly string $name, public readonly ?int $min, public readonly ?int $max)
    {
        if (! in_array($type, self::TYPES)) {
            throw new InvalidArgumentException(sprintf("The type '$type' is not a valid type. Valid types are: %s.", implode(', ', self::TYPES)));
        }

        if (($min !== null) && ($max !== null) && $max < $min) {
            throw new InvalidArgumentException("The 'max' value cannot be less than the 'min' value.");
        }
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'min'  => $this->min,
            'max'  => $this->max,
        ];
    }

    public function validateInputAgainstRules(string $input): bool
    {
        // TODO: Implement this method.

        return true;
    }
}
