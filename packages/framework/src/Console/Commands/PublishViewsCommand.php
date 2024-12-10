<?php

declare(strict_types=1);

namespace Hyde\Console\Commands;

use Hyde\Console\Concerns\Command;
use Hyde\Facades\Filesystem;
use Hyde\Foundation\Providers\ViewServiceProvider;
use Hyde\Hyde;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

use function Hyde\path_join;
use function Laravel\Prompts\multiselect;
use function str_replace;
use function sprintf;
use function strstr;

/**
 * Publish the Hyde Blade views.
 */
class PublishViewsCommand extends Command
{
    /** @var string */
    protected $signature = 'publish:views {category? : The category to publish} {--i|interactive : Interactively select the views to publish}';

    /** @var string */
    protected $description = 'Publish the hyde components for customization. Note that existing files will be overwritten';

    /** @var array<string, array<string, string>> */
    protected array $options = [
        'layouts' => [
            'name' => 'Blade Layouts',
            'description' => 'Shared layout views, such as the app layout, navigation menu, and Markdown page templates',
            'group' => 'hyde-layouts',
        ],
        'components' => [
            'name' => 'Blade Components',
            'description' => 'More or less self contained components, extracted for customizability and DRY code',
            'group' => 'hyde-components',
        ],
        'page-404' => [
            'name' => '404 Page',
            'description' => 'A beautiful 404 error page by the Laravel Collective',
            'group' => 'hyde-page-404',
        ],
    ];

    public function handle(): int
    {
        $selected = (string) ($this->argument('category') ?? $this->promptForCategory());

        if ($selected === 'all' || $selected === '') {
            foreach ($this->options as $key => $_ignored) {
                $this->publishOption($key);
            }
        } else {
            $this->publishOption($selected);
        }

        return Command::SUCCESS;
    }

    protected function isInteractive(): bool
    {
        return $this->option('interactive');
    }

    protected function publishOption(string $selected): void
    {
        // Todo: Don't trigger interactive if "all" is selected
        if ($this->isInteractive()) {
            $this->handleInteractivePublish($selected);

            return;
        }

        Artisan::call('vendor:publish', [
            '--tag' => $this->options[$selected]['group'] ?? $selected,
            '--force' => true,
        ], $this->output);
    }

    protected function promptForCategory(): string
    {
        /** @var string $choice */
        $choice = $this->choice(
            'Which category do you want to publish?',
            $this->formatPublishableChoices(),
            0
        );

        $selection = $this->parseChoiceIntoKey($choice);

        $this->infoComment(sprintf("Selected category [%s]\n", $selection ?: 'all'));

        return $selection;
    }

    protected function formatPublishableChoices(): array
    {
        $keys = ['Publish all categories listed below'];
        foreach ($this->options as $key => $option) {
            $keys[] = "<comment>$key</comment>: {$option['description']}";
        }

        return $keys;
    }

    protected function parseChoiceIntoKey(string $choice): string
    {
        return strstr(str_replace(['<comment>', '</comment>'], '', $choice), ':', true) ?: '';
    }

    protected function handleInteractivePublish(string $group): void
    {
        // Get all files in the components tag
        $paths = ServiceProvider::pathsToPublish(ViewServiceProvider::class, $this->options[$group]['group']);
        $source = key($paths);
        $target = $paths[$source];

        // Now we need an array that maps all source files to their target paths retaining the directory structure
        $search = File::allFiles($source);

        $files = collect($search)->mapWithKeys(/** @return array<string, string> */ function (SplFileInfo $file) use ($target): array {
            $targetPath = path_join($target, $file->getRelativePathname());

            return [Hyde::pathToRelative(realpath($file->getPathname())) => Hyde::pathToRelative($targetPath)];
        });

        // Now we need to prompt the user for which files to publish
        $selectedFiles = $this->promptForFiles($files, basename($target));

        // Now we filter the files to only include the selected ones
        $selectedFiles = $files->filter(fn (string $file): bool => in_array($file, $selectedFiles));

        // Now we need to publish the selected files
        foreach ($selectedFiles as $source => $target) {
            Filesystem::ensureDirectoryExists(dirname($target));
            Filesystem::copy($source, $target);
        }

        $this->infoComment(sprintf('Published files [%s]', collect($selectedFiles)->map(fn (string $file): string => Str::after($file, basename($source).'/'))->implode(', ')));
    }

    protected function promptForFiles(Collection $files, string $baseDir): array
    {
        $choices = $files->mapWithKeys(/** @return array<string, string> */ function (string $source) use ($baseDir): array {
            return [$source => Str::after($source, $baseDir.'/')];
        });

        return multiselect('Select the files you want to publish (CTRL+A to toggle all)', $choices, [], 10, 'required', hint: 'Navigate with arrow keys, space to select, enter to confirm.');
    }
}
