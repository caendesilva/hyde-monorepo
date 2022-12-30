<?php

declare(strict_types=1);

namespace Hyde\Framework\Actions;

use Hyde\Framework\Features\Publications\Models\PublicationFieldValues\PublicationFieldValue;
use function array_merge;
use Hyde\Framework\Actions\Concerns\CreateAction;
use Hyde\Framework\Actions\Contracts\CreateActionContract;
use Hyde\Framework\Features\Publications\Models\PublicationType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use function assert;
use function rtrim;
use RuntimeException;
use function substr;
use Symfony\Component\Yaml\Yaml;

/**
 * Scaffold a publication file.
 *
 * @see \Hyde\Console\Commands\MakePublicationCommand
 * @see \Hyde\Framework\Testing\Feature\Actions\CreatesNewPublicationPageTest
 */
class CreatesNewPublicationPage extends CreateAction implements CreateActionContract
{
    public function __construct(
        protected PublicationType $pubType,
        protected Collection $fieldData,
        protected bool $force = false,
    ) {
        $this->outputPath = "{$this->pubType->getDirectory()}/{$this->getFilename()}.md";
    }

    protected function handleCreate(): void
    {
        $output = "---
{$this->createFrontMatter()}
---

## Write something awesome.

";

        $this->save($output);
    }

    protected function getFilename(): string
    {
        return $this->formatStringForStorage(substr($this->getCanonicalValue(), 0, 64));
    }

    protected function getCanonicalValue(): string
    {
        if ($this->pubType->canonicalField === '__createdAt') {
            return Carbon::now()->format('Y-m-d H:i:s');
        }

        if ($this->fieldData->get($this->pubType->canonicalField)) {
            $field = $this->fieldData->get($this->pubType->canonicalField);
            assert($field instanceof PublicationFieldValue);

            return (string) $field->getValue();
        } else {
            return throw new RuntimeException("Could not find field value for '{$this->pubType->canonicalField}' which is required as it's the type's canonical field", 404);
        }
    }

    protected function createFrontMatter(): string
    {
        return rtrim(Yaml::dump($this->getMergedData(), flags: YAML::DUMP_MULTI_LINE_LITERAL_BLOCK));
    }

    protected function getMergedData(): array
    {
        return array_merge(['__createdAt' => Carbon::now()],
            $this->normalizeData($this->fieldData->toArray())
        );
    }

    /**
     * @deprecated Use FieldValue types instead
     *
     * @param  array<string, PublicationFieldValue>  $array
     * @return array<string, mixed>
     */
    public function normalizeData(array $array): array
    {
        foreach ($array as $key => $field) {
            assert($field instanceof PublicationFieldValue);

            $array[$key] = $field->getValue();
        }

        return $array;
    }
}
