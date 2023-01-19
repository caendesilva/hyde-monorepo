<?php

declare(strict_types=1);

namespace Hyde\Publications\Actions;

use Hyde\Framework\Concerns\InvokableAction;
use Hyde\Markdown\Models\MarkdownDocument;
use Hyde\Publications\Models\PublicationFieldDefinition;
use Hyde\Publications\Models\PublicationType;
use Illuminate\Contracts\Validation\Validator;
use function validator;

/**
 * @see \Hyde\Publications\Testing\Feature\PublicationPageValidatorTest
 */
class PublicationPageValidator extends InvokableAction
{
    protected PublicationType $publicationType;
    protected array $matter;

    protected Validator $validator;

    public function __construct(PublicationType $publicationType, string $pageIdentifier)
    {
        $this->publicationType = $publicationType;
        $this->matter = MarkdownDocument::parse("{$publicationType->getDirectory()}/$pageIdentifier.md")->matter()->toArray();
    }

    /** @return $this */
    public function __invoke(): static
    {
        $rules = [];
        $input = [];

        foreach ($this->publicationType->getFields() as $field) {
            $rules[$field->name] = $this->getValidationRules($field);
            $input[$field->name] = $this->matter[$field->name] ?? null;
        }

        $this->validator = validator($input, $rules);

        return $this;
    }

    /** @throws \Illuminate\Validation\ValidationException */
    public function validate(): void
    {
        $this->validator->validate();
    }

    /** @return array<int, string> */
    public function errors(): array
    {
        return $this->validator->errors()->all();
    }

    public function warnings(): array
    {
        $warnings = [];

        foreach ($this->matter as $key => $value) {
            // Check for extra fields that are not defined in the publication type (we'll add a warning for each one)
            $warnings[] = "Field '$key' is not defined in the schema.";
        }

        return $warnings;
    }

    protected function getValidationRules(PublicationFieldDefinition $field): array
    {
        return (new PublicationFieldValidator($this->publicationType, $field))->getValidationRules();
    }
}
