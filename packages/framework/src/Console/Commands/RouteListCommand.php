<?php

declare(strict_types=1);

namespace Hyde\Console\Commands;

use Hyde\Console\Concerns\Command;
use Hyde\Hyde;
use Hyde\Support\Models\Route;
use Hyde\Support\Models\RouteList;
use Hyde\Support\Models\RouteListItem;
use function file_exists;
use function sprintf;

/**
 * Hyde command to display the list of site routes.
 *
 * @see \Hyde\Framework\Testing\Feature\Commands\RouteListCommandTest
 */
class RouteListCommand extends Command
{
    /** @var string */
    protected $signature = 'route:list';

    /** @var string */
    protected $description = 'Display all registered routes.';

    public function handle(): int
    {
        $routes = $this->routeListClass();

        $this->table($routes->headers(), $routes->toArray());

        return Command::SUCCESS;
    }

    protected function routeListClass(): RouteList
    {
        return new class extends RouteList
        {
            protected static function routeToListItem(Route $route): RouteListItem
            {
                return new class($route) extends RouteListItem
                {
                    protected function stylePageType(string $class): string
                    {
                        $type = parent::stylePageType($class);

                        /** @experimental */
                        if ($type === 'InMemoryPage' && $this->route->getPage()->hasMacro('typeLabel')) {
                            $type .= sprintf(' <fg=gray>(%s)</>', $this->route->getPage()->typeLabel());
                        }

                        return $type;
                    }

                    protected function styleSourcePath(string $path): string
                    {
                        return parent::styleSourcePath($path) !== 'none'
                            ? $this->href(Command::createClickableFilepath(Hyde::path($path)), $path)
                            : '<fg=gray>none</>';
                    }

                    protected function styleOutputPath(string $path): string
                    {
                        return file_exists(Hyde::sitePath($path))
                            ? $this->href(Command::createClickableFilepath(Hyde::sitePath($path)), parent::styleOutputPath($path))
                            : parent::styleOutputPath($path);
                    }

                    /** @todo Move to base Command class */
                    protected function href(string $link, string $label): string
                    {
                        return "<href=$link>$label</>";
                    }
                };
            }
        };
    }
}
