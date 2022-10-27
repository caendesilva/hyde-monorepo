<?php

declare(strict_types=1);

namespace Hyde\Framework\Services;

use Closure;
use Hyde\Foundation\RouteCollection;
use Hyde\Framework\Actions\StaticPageBuilder;
use Hyde\Framework\Concerns\InteractsWithDirectories;
use Hyde\Hyde;
use Hyde\Routing\Route;
use Hyde\Support\Models\Site;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\File;

/**
 * Moves logic from the build command to a service.
 *
 * Handles the build loop which generates the static site.
 *
 * @see \Hyde\Console\Commands\BuildSiteCommand
 * @see \Hyde\Framework\Testing\Feature\StaticSiteServiceTest
 */
class BuildService
{
    use InteractsWithIO;
    use InteractsWithDirectories;

    protected RouteCollection $router;

    public function __construct(OutputStyle $output)
    {
        $this->output = $output;

        $this->router = Hyde::routes();
    }

    public function compileStaticPages(): void
    {
        $this->getDiscoveredModels()->each(function (string $pageClass) {
            $this->compilePagesForClass($pageClass);
        });
    }

    public function cleanOutputDirectory(): void
    {
        if (config('hyde.empty_output_directory', true)) {
            $this->warn('Removing all files from build directory.');

            if ($this->isItSafeToCleanOutputDirectory()) {
                array_map('unlink', glob(Hyde::sitePath('*.{html,json}'), GLOB_BRACE));
                File::cleanDirectory(Hyde::sitePath('media'));
            }
        }
    }

    public function transferMediaAssets(): void
    {
        $this->needsDirectory(Hyde::sitePath('media'));

        $collection = DiscoveryService::getMediaAssetFiles();
        $this->comment('Transferring Media Assets...');

        $this->withProgressBar(
            $collection,
            function ($filepath) {
                copy($filepath, Hyde::sitePath('media/'.basename($filepath)));
            }
        );
        $this->newLine(2);
    }

    /**
     * @return \Hyde\Foundation\RouteCollection<array-key, class-string<\Hyde\Framework\Concerns\HydePage>>
     */
    protected function getDiscoveredModels(): RouteCollection
    {
        return $this->router->getRoutes()->map(function (Route $route) {
            return $route->getPageType();
        })->unique();
    }

    protected function compilePagesForClass(string $pageClass): void
    {
        $this->comment("Creating {$this->getModelPluralName($pageClass)}...");

        $collection = $this->router->getRoutes($pageClass);

        $this->withProgressBar(
            $collection,
            $this->compileRoute()
        );

        $this->newLine(2);
    }

    /** @psalm-return \Closure(\Hyde\Routing\Route):string */
    protected function compileRoute(): Closure
    {
        return function (Route $route) {
            return (new StaticPageBuilder($route->getPage()))->__invoke();
        };
    }

    protected function getModelPluralName(string $pageClass): string
    {
        return preg_replace('/([a-z])([A-Z])/', '$1 $2', class_basename($pageClass)).'s';
    }

    protected function isItSafeToCleanOutputDirectory(): bool
    {
        if (! $this->isOutputDirectoryWhitelisted() && ! $this->askIfUnsafeDirectoryShouldBeEmptied()) {
            $this->info('Output directory will not be emptied.');

            return false;
        }

        return true;
    }

    protected function isOutputDirectoryWhitelisted(): bool
    {
        return in_array(
            basename(Hyde::sitePath()),
            config('hyde.safe_output_directories', ['_site', 'docs', 'build'])
        );
    }

    protected function askIfUnsafeDirectoryShouldBeEmptied(): bool
    {
        return $this->confirm(sprintf(
            'The configured output directory (%s) is potentially unsafe to empty. '.
            'Are you sure you want to continue?',
            Site::$outputPath
        ));
    }
}
