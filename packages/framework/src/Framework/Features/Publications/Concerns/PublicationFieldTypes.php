<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Publications\Concerns;

use BadMethodCallException;

/**
 * The supported field types for publication types.
 *
 * @see \Hyde\Framework\Features\Publications\Models\PublicationFieldType
 * @see \Hyde\Framework\Testing\Feature\PublicationFieldTypesEnumTest
 */
enum PublicationFieldTypes: string
{
    case String = 'string';
    case Boolean = 'boolean';
    case Integer = 'integer';
    case Float = 'float';
    case Datetime = 'datetime';
    case Url = 'url';
    case Array = 'array';
    case Text = 'text';
    case Image = 'image';
    case Tag = 'tag';

    protected final const DEFAULT_RULES = [
        'string'   => ['required', 'string', 'between'],
        'boolean'  => ['required', 'boolean'],
        'integer'  => ['required', 'integer', 'between'],
        'float'    => ['required', 'numeric', 'between'],
        'datetime' => ['required', 'datetime', 'between'],
        'url'      => ['required', 'url'],
        'text'     => ['required', 'string', 'between'],
    ];

    public function rules(): array
    {
        return self::DEFAULT_RULES[$this->value];
    }

    public static function getRules(self $type): array
    {
        return match ($type) {
            self::String => ['required', 'string', 'between'],
            self::Boolean => ['required', 'boolean'],
            self::Integer => ['required', 'integer', 'between'],
            self::Float => ['required', 'numeric', 'between'],
            self::Datetime => ['required', 'datetime', 'between'],
            self::Url => ['required', 'url'],
            self::Text => ['required', 'string', 'between'],
            self::Array => throw new BadMethodCallException('This type has no validation rules'),
            self::Image => throw new BadMethodCallException('This type has no validation rules'),
            self::Tag => throw new BadMethodCallException('This type has no validation rules'),
        };
    }
}
