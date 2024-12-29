<?php

declare(strict_types=1);

namespace Hyde\Framework\Services;

use Hyde\Facades\Config;
use Hyde\Facades\Features;
use Hyde\Facades\Filesystem;
use Hyde\Framework\Features\BuildTasks\BuildTask;
use Hyde\Framework\Features\BuildTasks\PreBuildTask;
use Hyde\Framework\Features\BuildTasks\PostBuildTask;
use Hyde\Framework\Actions\PreBuildTasks\CleanSiteDirectory;
use Hyde\Framework\Actions\PostBuildTasks\GenerateRssFeed;
use Hyde\Framework\Actions\PostBuildTasks\GenerateSitemap;
use Hyde\Framework\Actions\PreBuildTasks\TransferMediaAssets;
use Hyde\Framework\Actions\PostBuildTasks\GenerateBuildManifest;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Str;

use function array_map;
use function array_values;
use function class_basename;
use function is_string;
use function str_replace;

/**
 * This service manages the build tasks that are called before and after the site is compiled using the build command.
 *
 * The class is registered as a singleton in the Laravel service container and is run by the build command.
 * Build Tasks can be registered programmatically, through the config, and through autodiscovery.
 * The service determines when to run a task depending on which class it extends.
 */
class BuildTaskService
{
    /** @var array<string, \Hyde\Framework\Features\BuildTasks\BuildTask> */
    protected array $buildTasks = [];

    protected ?OutputStyle $output = null;

    public function __construct()
    {
        $this->registerFrameworkTasks();

        $this->registerTasks($this->findTasksInConfig());

        $this->registerTasks($this->findTasksInAppDirectory());
    }

    public function setOutput(?OutputStyle $output): void
    {
        $this->output = $output;
    }

    /** @return array<class-string<\Hyde\Framework\Features\BuildTasks\PreBuildTask>|class-string<\Hyde\Framework\Features\BuildTasks\PostBuildTask>> */
    public function getRegisteredTasks(): array
    {
        return array_map(fn (BuildTask $task): string => $task::class, array_values($this->buildTasks));
    }

    public function runPreBuildTasks(): void
    {
        foreach ($this->buildTasks as $task) {
            if ($task instanceof PreBuildTask) {
                $task->run($this->output);
            }
        }
    }

    public function runPostBuildTasks(): void
    {
        foreach ($this->buildTasks as $task) {
            if ($task instanceof PostBuildTask) {
                $task->run($this->output);
            }
        }
    }

    /** @param  \Hyde\Framework\Features\BuildTasks\PreBuildTask|\Hyde\Framework\Features\BuildTasks\PostBuildTask|class-string<\Hyde\Framework\Features\BuildTasks\PreBuildTask|\Hyde\Framework\Features\BuildTasks\PostBuildTask>  $task */
    public function registerTask(PreBuildTask|PostBuildTask|string $task): void
    {
        $this->registerTaskInService(is_string($task) ? new $task() : $task);
    }

    protected function registerTaskInService(PreBuildTask|PostBuildTask $task): void
    {
        $this->buildTasks[$this->makeTaskIdentifier($task)] = $task;
    }

    /** @param class-string<\Hyde\Framework\Features\BuildTasks\PreBuildTask|\Hyde\Framework\Features\BuildTasks\PostBuildTask> $task */
    protected function registerIf(string $task, bool $condition): void
    {
        if ($condition) {
            $this->registerTask($task);
        }
    }

    /** @param array<\Hyde\Framework\Features\BuildTasks\PreBuildTask|\Hyde\Framework\Features\BuildTasks\PostBuildTask|class-string<\Hyde\Framework\Features\BuildTasks\PreBuildTask|\Hyde\Framework\Features\BuildTasks\PostBuildTask>> $tasks */
    protected function registerTasks(array $tasks): void
    {
        foreach ($tasks as $task) {
            $this->registerTask($task);
        }
    }

    /** @return array<class-string<\Hyde\Framework\Features\BuildTasks\PreBuildTask>|class-string<\Hyde\Framework\Features\BuildTasks\PostBuildTask>> */
    protected function findTasksInConfig(): array
    {
        return Config::getArray('hyde.build_tasks', []);
    }

    /** @return array<class-string<\Hyde\Framework\Features\BuildTasks\PreBuildTask>|class-string<\Hyde\Framework\Features\BuildTasks\PostBuildTask>> */
    protected function findTasksInAppDirectory(): array
    {
        return Filesystem::smartGlob('app/Actions/*BuildTask.php')->map(function (string $file): string {
            return static::pathToClassName($file);
        })->toArray();
    }

    protected static function pathToClassName(string $file): string
    {
        return str_replace(['app', '.php', '/'], ['App', '', '\\'], $file);
    }

    protected function makeTaskIdentifier(BuildTask $class): string
    {
        // If a user-land task is registered with the same class name (excluding namespaces) as a framework task,
        // this will allow the user-land task to override the framework task, making them easy to swap out.

        return Str::kebab(class_basename($class));
    }

    private function registerFrameworkTasks(): void
    {
        $this->registerIf(CleanSiteDirectory::class, $this->canCleanSiteDirectory());
        $this->registerIf(TransferMediaAssets::class, $this->canTransferMediaAssets());
        $this->registerIf(GenerateBuildManifest::class, $this->canGenerateManifest());
        $this->registerIf(GenerateSitemap::class, $this->canGenerateSitemap());
        $this->registerIf(GenerateRssFeed::class, $this->canGenerateFeed());
    }

    private function canCleanSiteDirectory(): bool
    {
        return Config::getBool('hyde.empty_output_directory', true);
    }

    private function canTransferMediaAssets(): bool
    {
        return Config::getBool('hyde.transfer_media_assets', true);
    }

    private function canGenerateManifest(): bool
    {
        return Config::getBool('hyde.generate_build_manifest', true);
    }

    private function canGenerateSitemap(): bool
    {
        return Features::hasSitemap();
    }

    private function canGenerateFeed(): bool
    {
        return Features::hasRss();
    }
}
