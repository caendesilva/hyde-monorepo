<?php

declare(strict_types=1);

namespace Hyde\Console\Commands;

use Hyde\Hyde;
use Hyde\Support\Models\Route;
use Hyde\Console\Concerns\Command;
use Hyde\Support\Internal\RouteListItem;

use function array_map;
use function array_keys;
use function array_values;
use function Hyde\make_title;

/**
 * Display the list of site routes.
 */
class RouteListCommand extends Command
{
    /** @var string */
    protected $signature = 'route:list';

    /** @var string */
    protected $description = 'Display all the registered routes';

    public function handle(): int
    {
        $routes = $this->generate();

        $this->table($this->makeHeader($routes), $routes);

        return Command::SUCCESS;
    }

    protected function generate(): array
    {
        return array_map(function (Route $route): array {
            return (new RouteListItem($route))->getColumns();
        }, array_values(Hyde::routes()->all()));
    }

    protected function makeHeader(array $routes): array
    {
        return array_map(function (string $key): string {
            return make_title($key);
        }, array_keys($routes[0]));
    }
}
