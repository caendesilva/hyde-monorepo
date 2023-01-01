<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Publications\Models;

use function array_filter;
use function collect;
use Hyde\Framework\Features\Publications\PublicationFieldTypes;
use Hyde\Framework\Features\Publications\PublicationService;
use Hyde\Support\Concerns\Serializable;
use Hyde\Support\Contracts\SerializableContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use function str_starts_with;
use function strtolower;

/**
 * Represents an entry in the "fields" array of a publication type schema.
 *
 * @see \Hyde\Framework\Features\Publications\PublicationFieldTypes
 * @see \Hyde\Framework\Testing\Feature\PublicationFieldDefinitionTest
 */
class PublicationFieldDefinition implements SerializableContract
{
    use Serializable;

    public readonly PublicationFieldTypes $type;
    public readonly string $name;
    public readonly array $rules;

    public static function fromArray(array $array): static
    {
        return new static(...$array);
    }

    public function __construct(PublicationFieldTypes|string $type, string $name, array $rules = [])
    {
        $this->type = $type instanceof PublicationFieldTypes ? $type : PublicationFieldTypes::from(strtolower($type));
        $this->name = str_starts_with($name, '__') ? $name : Str::kebab($name);
        $this->rules = $rules;
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type->value,
            'name' => $this->name,
            'rules' => $this->rules,
        ]);
    }

    /**
     * @param  \Hyde\Framework\Features\Publications\Models\PublicationType|null  $publicationType  Required only when using the 'image' type.
     *
     * @see https://laravel.com/docs/9.x/validation#available-validation-rules
     */
    public function getValidationRules(?PublicationType $publicationType = null): Collection
    {
        $fieldClass = $this->type->fieldClass();
        $fieldRules = collect($fieldClass::rules());

        // Here we could check for a "strict" mode type of thing and add 'required' to the rules if we wanted to.

        // Apply any custom field rules.
        $fieldRules->push(...$this->rules);

        // Apply any dynamic rules.
        switch ($this->type->value) {
            case 'image':
                $mediaFiles = PublicationService::getMediaForPubType($publicationType);
                $valueList = $mediaFiles->implode(',');
                $fieldRules->add("in:$valueList");
                break;
            case 'tag':
                $tagValues = PublicationService::getValuesForTagName($publicationType?->getIdentifier() ?? '') ?? collect([]);
                $valueList = $tagValues->implode(',');
                $fieldRules->add("in:$valueList");
                break;
        }

        return $fieldRules;
    }

    /** @param \Hyde\Framework\Features\Publications\Models\PublicationType|null $publicationType Required only when using the 'image' type. */
    public function validate(mixed $input = null, Arrayable|array|null $fieldRules = null, ?PublicationType $publicationType = null): array
    {
        $rules = $this->evaluateArrayable($fieldRules ?? $this->getValidationRules($publicationType));

        return validator([$this->name => $input], [$this->name => $rules])->validate();
    }

    /** @deprecated Will be moved to a generic helper function */
    protected function evaluateArrayable(array|Arrayable $array): array
    {
        return $array instanceof Arrayable ? $array->toArray() : $array;
    }
}
