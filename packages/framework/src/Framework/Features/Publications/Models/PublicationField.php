<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Publications\Models;

use function collect;
use Hyde\Framework\Features\Publications\PublicationFieldTypes;
use Hyde\Framework\Features\Publications\PublicationService;
use Hyde\Support\Concerns\Serializable;
use Hyde\Support\Contracts\SerializableContract;
use Illuminate\Support\Str;
use Rgasch\Collection\Collection;
use function strtolower;

/**
 * Represents an entry in the "fields" array of a publication type schema.
 *
 * @see \Hyde\Framework\Features\Publications\PublicationFieldTypes
 * @see \Hyde\Framework\Testing\Feature\PublicationFieldTest
 */
class PublicationField implements SerializableContract
{
    use Serializable;

    public readonly PublicationFieldTypes $type;
    public readonly string $name;
    public readonly ?string $tagGroup;
    /** @deprecated Since this is only used one method it will be moved to that method */
    public readonly ?PublicationType $publicationType; // Only used for validation command, interactive command doesn't need this

    public static function fromArray(array $array): static
    {
        return new static(...$array);
    }

    public function __construct(PublicationFieldTypes|string $type, string $name, ?string $tagGroup = null, PublicationType $publicationType = null)
    {
        $this->type = $type instanceof PublicationFieldTypes ? $type : PublicationFieldTypes::from(strtolower($type));
        $this->name = Str::kebab($name);
        $this->tagGroup = $tagGroup;
        $this->publicationType = $publicationType;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'name' => $this->name,
            'tagGroup' => $this->tagGroup,
        ];
    }

    /**
     * @see \Hyde\Framework\Testing\Unit\PublicationFieldTypeValidationRulesTest
     * @see https://laravel.com/docs/9.x/validation#available-validation-rules
     */
    public function getValidationRules(bool $reload = true): Collection
    {
        $defaultRules = Collection::create(PublicationFieldTypes::values());
        $fieldRules = Collection::create($defaultRules->get($this->type->value));

        switch ($this->type->value) {
            case 'array':
                $fieldRules->add('array');
                break;
            case 'datetime':
                $fieldRules->add('date');

                break;
            case 'float':
                $fieldRules->add('numeric');
                break;
            case 'integer':
            case 'string':
            case 'text':
                break;
            case 'image':
                $mediaFiles = PublicationService::getMediaForPubType($this->publicationType, $reload);
                $valueList = $mediaFiles->implode(',');
                $fieldRules->add("in:$valueList");
                break;
            case 'tag':
                $tagValues = PublicationService::getValuesForTagName($this->tagGroup, $reload) ?? collect([]);
                $valueList = $tagValues->implode(',');
                $fieldRules->add("in:$valueList");
                break;
            case 'url':
                $fieldRules->add('url');
                break;
        }

        return $fieldRules;
    }

    public function validate(mixed $input = null, Collection $fieldRules = null): array
    {
        if (! $fieldRules) {
            $fieldRules = $this->getValidationRules(false);
        }

        $validator = validator([$this->name => $input], [$this->name => $fieldRules->toArray()]);

        return $validator->validate();
    }
}
