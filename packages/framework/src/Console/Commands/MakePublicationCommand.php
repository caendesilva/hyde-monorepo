<?php

declare(strict_types=1);

namespace Hyde\Console\Commands;

use Closure;
use Hyde\Console\Commands\Helpers\InputStreamHandler;
use Hyde\Console\Concerns\ValidatingCommand;
use Hyde\Framework\Actions\CreatesNewPublicationPage;
use Hyde\Framework\Features\Publications\Models\PublicationField;
use Hyde\Framework\Features\Publications\Models\PublicationFieldValues\ArrayField;
use Hyde\Framework\Features\Publications\Models\PublicationFieldValues\ImageField;
use Hyde\Framework\Features\Publications\Models\PublicationFieldValues\PublicationFieldValue;
use Hyde\Framework\Features\Publications\Models\PublicationFieldValues\TagField;
use Hyde\Framework\Features\Publications\Models\PublicationFieldValues\TextField;
use Hyde\Framework\Features\Publications\Models\PublicationType;
use Hyde\Framework\Features\Publications\PublicationFieldTypes;
use Hyde\Framework\Features\Publications\PublicationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use function implode;
use function in_array;
use InvalidArgumentException;
use LaravelZero\Framework\Commands\Command;
use function str_starts_with;

/**
 * Hyde Command to create a new publication for a given publication type.
 *
 * @see \Hyde\Framework\Actions\CreatesNewPublicationPage
 * @see \Hyde\Framework\Testing\Feature\Commands\MakePublicationCommandTest
 */
class MakePublicationCommand extends ValidatingCommand
{
    /** @var string */
    protected $signature = 'make:publication
		{publicationType? : The name of the publication type to create a publication for}
        {--force : Should the generated file overwrite existing publications with the same filename?}';

    /** @var string */
    protected $description = 'Create a new publication item';

    protected PublicationType $publicationType;

    /** @var \Illuminate\Support\Collection<string, PublicationType> */
    protected Collection $fieldData;

    public function safeHandle(): int
    {
        $this->title('Creating a new publication!');

        $this->publicationType = $this->getPublicationTypeSelection();
        $this->fieldData = new Collection();

        $this->collectFieldData();

        $creator = new CreatesNewPublicationPage($this->publicationType, $this->fieldData, (bool) $this->option('force'));
        if ($creator->hasFileConflict()) {
            $this->error('Error: A publication already exists with the same canonical field value');
            if ($this->confirm('Do you wish to overwrite the existing file?')) {
                $creator->force();
            } else {
                $this->info('Exiting without overwriting existing publication file!');

                return ValidatingCommand::USER_EXIT;
            }
        }
        $creator->create();

        $this->infoComment("All done! Created file", $creator->getOutputPath());

        return Command::SUCCESS;
    }

    protected function getPublicationTypeSelection(): PublicationType
    {
        $publicationTypes = $this->getPublicationTypes();
        $publicationTypeSelection = $this->argument('publicationType') ??
        $this->choice(
            'Which publication type would you like to create a publication item for?',
            $publicationTypes->keys()->toArray()
        );

        if ($publicationTypes->has($publicationTypeSelection)) {
            $this->line("<info>Creating a new publication of type</info> [<comment>$publicationTypeSelection</comment>]");

            return $publicationTypes->get($publicationTypeSelection);
        }

        throw new InvalidArgumentException("Unable to locate publication type [$publicationTypeSelection]");
    }

    protected function getPublicationTypes(): Collection
    {
        $publicationTypes = PublicationService::getPublicationTypes();
        if ($publicationTypes->isEmpty()) {
            throw new InvalidArgumentException('Unable to locate any publication types. Did you create any?');
        }

        return $publicationTypes;
    }

    protected function collectFieldData(): void
    {
        $this->newLine();
        $this->info('Now please enter the field data:');

        /** @var PublicationField $field */
        foreach ($this->publicationType->getFields() as $field) {
            if (str_starts_with($field->name, '__')) {
                continue;
            }

            $this->newLine();
            $fieldInput = $this->captureFieldInput($field);
            if (empty($fieldInput)) {
                $this->line("<fg=gray> > Skipping field $field->name</>");
            } else {
                $this->fieldData->put($field->name, $fieldInput);
            }
        }

        $this->newLine();
    }

    protected function captureFieldInput(PublicationField $field): ?PublicationFieldValue
    {
        return match ($field->type) {
            PublicationFieldTypes::Text => $this->captureTextFieldInput($field),
            PublicationFieldTypes::Array => $this->captureArrayFieldInput($field),
            PublicationFieldTypes::Image => $this->captureImageFieldInput($field),
            PublicationFieldTypes::Tag => $this->captureTagFieldInput($field),
            default => $this->captureOtherFieldInput($field),
        };
    }

    protected function captureTextFieldInput(PublicationField $field): TextField
    {
        $this->infoComment(' Enter lines for field', $field->name, '</>(end with an empty line)');

        return new TextField(implode("\n", InputStreamHandler::call()));
    }

    protected function captureArrayFieldInput(PublicationField $field): ArrayField
    {
        $this->infoComment(' Enter values for field', $field->name, '</>(end with an empty line)');

        return new ArrayField(InputStreamHandler::call());
    }

    protected function captureImageFieldInput(PublicationField $field): ?ImageField
    {
        $this->infoComment(' Select file for image field', $field->name);

        $mediaFiles = PublicationService::getMediaForPubType($this->publicationType);
        if ($mediaFiles->isEmpty()) {
            return $this->handleEmptyOptionsCollection($field, 'media file', "No media files found in directory _media/{$this->publicationType->getIdentifier()}/");
        }

        return new ImageField($this->choice('Which file would you like to use?', $mediaFiles->toArray()));
    }

    protected function captureTagFieldInput(PublicationField $field): ?TagField
    {
        $this->infoComment(' Select a tag for field', $field->name, "from the {$this->publicationType->getIdentifier()} group");

        $options = PublicationService::getValuesForTagName($this->publicationType->getIdentifier());
        if ($options->isEmpty()) {
            return $this->handleEmptyOptionsCollection($field, 'tag', 'No tags for this publication type found in tags.json');
        }

        $this->tip('You can enter multiple tags separated by commas');

        $choice = $this->reloadableChoice($this->getReloadableTagValuesArrayClosure(),
            'Which tag would you like to use?',
            'Reload tags.json',
            true
        );

        return new TagField($choice);
    }

    protected function captureOtherFieldInput(PublicationField $field): ?PublicationFieldValue
    {
        $selection = $this->askForFieldData($field->name, $field->getValidationRules()->toArray());
        if (empty($selection)) {
            return null;
        }

        $namespace = Str::beforeLast(PublicationFieldValue::class, '\\');
        $className = "$namespace\\{$field->type->name}Field";

        return new $className($selection);
    }

    protected function askForFieldData(string $name, array $rules): string
    {
        return $this->askWithValidation($name, "Enter data for field </>[<comment>$name</comment>]", $rules);
    }

    /** @return null */
    protected function handleEmptyOptionsCollection(PublicationField $field, string $type, string $message)
    {
        if (in_array('required', $field->rules)) {
            throw new InvalidArgumentException("Unable to create publication: $message");
        }

        $this->newLine();
        $this->warn(" <fg=red>Warning:</> $message");
        if ($this->confirm('Would you like to skip this field?', true)) {
            return null;
        } else {
            throw new InvalidArgumentException("Unable to locate any {$type}s for this publication type");
        }
    }

    protected function tip(string $message): void
    {
        $this->line("<fg=bright-blue>Tip:</> $message");
    }

    /** @return Closure<array<string>> */
    protected function getReloadableTagValuesArrayClosure(): Closure
    {
        return function (): array {
            return PublicationService::getValuesForTagName($this->publicationType->getIdentifier())->toArray();
        };
    }
}
