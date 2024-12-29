<?php

declare(strict_types=1);

namespace Hyde\Console\Commands;

use Closure;
use Hyde\Console\Concerns\Command;
use Hyde\Console\Helpers\ConsoleHelper;
use Hyde\Console\Helpers\InteractivePublishCommandHelper;
use Hyde\Console\Helpers\ViewPublishGroup;
use Illuminate\Support\Str;
use Laravel\Prompts\Key;
use Laravel\Prompts\MultiSelectPrompt;
use Laravel\Prompts\SelectPrompt;

use function Laravel\Prompts\select;
use function str_replace;
use function sprintf;
use function strstr;

/**
 * Publish the Hyde Blade views.
 */
class PublishViewsCommand extends Command
{
    /** @var string */
    protected $signature = 'publish:views {category? : The category to publish}';

    /** @var string */
    protected $description = 'Publish the Hyde components for customization. Note that existing files will be overwritten';

    /** @var array<string, \Hyde\Console\Helpers\ViewPublishGroup> */
    protected array $options;

    public function handle(): int
    {
        $this->options = static::mapToKeys([
            ViewPublishGroup::fromGroup('hyde-layouts', 'Blade Layouts', 'Shared layout views, such as the app layout, navigation menu, and Markdown page templates'),
            ViewPublishGroup::fromGroup('hyde-components', 'Blade Components', 'More or less self contained components, extracted for customizability and DRY code'),
        ]);

        $selected = ($this->argument('category') ?? $this->promptForCategory()) ?: 'all';

        if ($selected !== 'all' && (bool) $this->argument('category') === false && ConsoleHelper::canUseLaravelPrompts($this->input)) {
            $this->infoComment(sprintf('Selected category [%s]', $selected));
        }

        if (! in_array($selected, $allowed = array_merge(['all'], array_keys($this->options)), true)) {
            $this->error("Invalid selection: '$selected'");
            $this->infoComment('Allowed values are: ['.implode(', ', $allowed).']');

            return Command::FAILURE;
        }

        $files = $selected === 'all'
            ? collect($this->options)->flatMap(fn (ViewPublishGroup $option): array => $option->publishableFilesMap())->all()
            : $this->options[$selected]->publishableFilesMap();

        $publisher = $this->publishSelectedFiles($files, $selected === 'all');

        $this->infoComment($publisher->formatOutput($selected));

        return Command::SUCCESS;
    }

    protected function promptForCategory(): string
    {
        SelectPrompt::fallbackUsing(function (SelectPrompt $prompt): string {
            return $this->choice($prompt->label, $prompt->options, $prompt->default);
        });

        return $this->parseChoiceIntoKey(
            select('Which category do you want to publish?', $this->formatPublishableChoices(), 0) ?: 'all'
        );
    }

    protected function formatPublishableChoices(): array
    {
        return collect($this->options)
            ->map(fn (ViewPublishGroup $option, string $key): string => sprintf('<comment>%s</comment>: %s', $key, $option->description))
            ->prepend('Publish all categories listed below')
            ->values()
            ->all();
    }

    protected function parseChoiceIntoKey(string $choice): string
    {
        return strstr(str_replace(['<comment>', '</comment>'], '', $choice), ':', true) ?: '';
    }

    /**
     * @param  array<string, \Hyde\Console\Helpers\ViewPublishGroup>  $groups
     * @return array<string, \Hyde\Console\Helpers\ViewPublishGroup>
     */
    protected static function mapToKeys(array $groups): array
    {
        return collect($groups)->mapWithKeys(function (ViewPublishGroup $group): array {
            return [Str::after($group->group, 'hyde-') => $group];
        })->all();
    }

    /** @param  array<string, string>  $files */
    protected function publishSelectedFiles(array $files, bool $isPublishingAll): InteractivePublishCommandHelper
    {
        $publisher = new InteractivePublishCommandHelper($files);

        if (! $isPublishingAll && ConsoleHelper::canUseLaravelPrompts($this->input)) {
            $publisher->only($this->promptUserForWhichFilesToPublish($publisher->getFileChoices()));
        }

        $publisher->publishFiles();

        return $publisher;
    }

    /**
     * @param  array<string, string>  $files
     * @return array<string>
     */
    protected function promptUserForWhichFilesToPublish(array $files): array
    {
        $choices = array_merge(['all' => '<comment>All files</comment>'], $files);

        $prompt = new MultiSelectPrompt('Select the files you want to publish', $choices, [], 10, 'required', hint: 'Navigate with arrow keys, space to select, enter to confirm.');

        $prompt->on('key', static::supportTogglingAll($prompt));

        return (array) $prompt->prompt();
    }

    protected static function supportTogglingAll(MultiSelectPrompt $prompt): Closure
    {
        return function (string $key) use ($prompt): void {
            static $isToggled = false;

            if ($prompt->isHighlighted('all')) {
                if ($key === Key::SPACE) {
                    $prompt->emit('key', Key::CTRL_A);

                    if ($isToggled) {
                        // We need to emit CTRL+A twice to deselect all for some reason
                        $prompt->emit('key', Key::CTRL_A);
                        $isToggled = false;
                    } else {
                        $isToggled = true;
                    }
                } elseif ($key === Key::ENTER) {
                    if (! $isToggled) {
                        $prompt->emit('key', Key::CTRL_A);
                    }

                    $prompt->state = 'submit';
                }
            }
        };
    }
}
