<?php

declare(strict_types=1);

namespace Hyde\Console\Commands;

use Hyde\Console\Commands\Helpers\InputStreamHandler;
use Hyde\Console\Concerns\ValidatingCommand;
use Hyde\Framework\Actions\CreatesNewPublicationPage;
use Hyde\Framework\Features\Publications\Models\PublicationField;
use Hyde\Framework\Features\Publications\Models\PublicationType;
use Hyde\Framework\Features\Publications\PublicationFieldTypes;
use Hyde\Framework\Features\Publications\PublicationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use function implode;
use InvalidArgumentException;
use LaravelZero\Framework\Commands\Command;

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
		{publicationType? : The name of the PublicationType to create a publication for}
        {--force : Should the generated file overwrite existing publications with the same filename?}';

    /** @var string */
    protected $description = 'Create a new publication item';

    protected PublicationType $publicationType;

    public function safeHandle(): int
    {
        $this->title('Creating a new Publication!');

        $this->publicationType = $this->getPubTypeSelection($this->getPublicationTypes());
        $fieldData = $this->collectFieldData();

        $creator = new CreatesNewPublicationPage($this->publicationType, $fieldData, $this->hasForceOption(), $this->output);
        if ($creator->hasFileConflict()) {
            $this->error('Error: A publication already exists with the same canonical field value');
            if ($this->confirm('Do you wish to overwrite the existing file?')) {
                $creator->force();
            } else {
                $this->output->writeln('<bg=magenta;fg=white>Exiting without overwriting existing publication file!</>');

                return ValidatingCommand::USER_EXIT;
            }
        }

        $creator->create();

        $this->info('Publication created successfully!');

        return Command::SUCCESS;
    }

    protected function captureFieldInput(PublicationField $field): string|array
    {
        if ($field->type === PublicationFieldTypes::Text) {
            return $this->captureTextFieldInput($field);
        }

        if ($field->type === PublicationFieldTypes::Array) {
            return $this->captureArrayFieldInput($field);
        }

        if ($field->type === PublicationFieldTypes::Image) {
            return $this->captureImageFieldInput($field);
        }

        if ($field->type === PublicationFieldTypes::Tag) {
            return $this->captureTagFieldInput($field);
        }

        $fieldRules = $this->generateFieldRules($field);

        return $this->askWithValidation($field->name, $field->name, $fieldRules->toArray());
    }

    /**
     * @param  \Illuminate\Support\Collection<string, \Hyde\Framework\Features\Publications\Models\PublicationType>  $publicationTypes
     * @return \Hyde\Framework\Features\Publications\Models\PublicationType
     */
    protected function getPubTypeSelection(Collection $publicationTypes): PublicationType
    {
        $publicationTypeSelection = $this->argument('publicationType') ?? $publicationTypes->keys()->get(
            (int) $this->choice('Which publication type would you like to create a publication item for?',
                $publicationTypes->keys()->toArray()
            )
        );

        if ($publicationTypes->has($publicationTypeSelection)) {
            $this->line("<info>Creating a new publication of type</info> [<comment>$publicationTypeSelection</comment>]");

            return $publicationTypes->get($publicationTypeSelection);
        }

        throw new InvalidArgumentException("Unable to locate publication type [$publicationTypeSelection]");
    }

    /**
     * @return \Illuminate\Support\Collection<string, string|array>
     */
    protected function collectFieldData(): Collection
    {
        $this->output->writeln("\n<bg=magenta;fg=white>Now please enter the field data:</>");

        $data = new Collection();

        /** @var PublicationField $field */
        foreach ($this->publicationType->getFields() as $field) {
            $data->put($field->name, $this->captureFieldInput($field));
        }

        return $data;
    }

    /**
     * @return \Illuminate\Support\Collection<string, PublicationType>
     *
     * @throws \InvalidArgumentException
     */
    protected function getPublicationTypes(): Collection
    {
        $publicationTypes = PublicationService::getPublicationTypes();
        if ($publicationTypes->isEmpty()) {
            throw new InvalidArgumentException('Unable to locate any publication types. Did you create any?');
        }

        return $publicationTypes;
    }

    protected function hasForceOption(): bool
    {
        return (bool) $this->option('force');
    }

    protected function captureTextFieldInput(PublicationField $field): string
    {
        $this->output->writeln($field->name.' (end with an empty line)');

        return implode("\n", InputStreamHandler::call());
    }

    protected function captureArrayFieldInput(PublicationField $field): array
    {
        $this->output->writeln($field->name.' (end with an empty line)');

        return InputStreamHandler::call();
    }

    protected function captureImageFieldInput(PublicationField $field): string
    {
        $this->output->writeln($field->name.' (end with an empty line)');
        do {
            $offset = 0;
            $mediaFiles = PublicationService::getMediaForPubType($this->publicationType);
            foreach ($mediaFiles as $index => $file) {
                $offset = $index + 1;
                $this->output->writeln("  $offset: $file");
            }
            $selected = (int) $this->askWithValidation($field->name, $field->name, ['required', 'integer', "between:1,$offset"]);
        } while ($selected == 0);
        $file = $mediaFiles->{$selected - 1};

        return '_media/'.Str::of($file)->after('media/')->toString();
    }

    protected function captureTagFieldInput(PublicationField $field)
    {
        $this->output->writeln($field->name.' (enter 0 to reload tag definitions)');
        do {
            $offset = 0;
            $tagsForGroup = PublicationService::getAllTags()->{$this->publicationType->name};
            foreach ($tagsForGroup as $index => $value) {
                $offset = $index + 1;
                $this->output->writeln("  $offset: $value");
            }
            $selected = (int) $this->askWithValidation($field->name, $field->name, ['required', 'integer', "between:0,$offset"]);
        } while ($selected == 0);

        return $tagsForGroup->{$selected - 1};
    }

    // Get rules for fields which are not of type array, text or image
    protected function generateFieldRules(PublicationField $field): Collection
    {
        $fieldRules = Collection::make($field->type->rules());
        if ($fieldRules->contains('between')) {
            $fieldRules->forget($fieldRules->search('between'));
        }

        return $fieldRules;
    }
}
