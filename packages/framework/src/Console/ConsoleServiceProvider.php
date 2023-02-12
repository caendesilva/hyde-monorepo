<?php

declare(strict_types=1);

namespace Hyde\Console;

use Illuminate\Support\ServiceProvider;

/**
 * Register the HydeCLI console commands.
 */
class ConsoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            Commands\BuildRssFeedCommand::class,
            Commands\BuildSearchCommand::class,
            Commands\BuildSiteCommand::class,
            Commands\BuildSitemapCommand::class,
            Commands\RebuildStaticPageCommand::class,

            Commands\MakePageCommand::class,
            Commands\MakePostCommand::class,

            Commands\VendorPublishCommand::class,
            Commands\PublishHomepageCommand::class,
            Commands\PublishViewsCommand::class,
            Commands\PublishConfigsCommand::class,
            Commands\PackageDiscoverCommand::class,

            Commands\RouteListCommand::class,
            Commands\ValidateCommand::class,
            Commands\ServeCommand::class,
            Commands\DebugCommand::class,

            Commands\ChangeSourceDirectoryCommand::class,
        ]);
    }

    public function boot(): void
    {
        //
    }
}
