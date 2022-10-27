<?php

declare(strict_types=1);

namespace Hyde\Routing;

use Hyde\Foundation\RouteCollection;
use Hyde\Framework\Concerns\HydePage;
use Hyde\Framework\Exceptions\RouteNotFoundException;
use Hyde\Hyde;
use Hyde\Support\Concerns\JsonSerializesArrayable;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Stringable;

/**
 * The Route class bridges the gaps between Hyde pages and their respective compiled static webpages
 * by providing helper methods and information allowing you to easily access and interact with the
 * various paths associated with a page, both source and compiled file paths as well as the URL.
 *
 * If you visualize a web of this class's properties, you should be able to see how this
 * class links them all together, and what powerful information you can gain from it.
 *
 * @see \Hyde\Framework\Testing\Feature\RouteTest
 */
class Route implements Stringable, JsonSerializable, Arrayable
{
    use JsonSerializesArrayable;

    /**
     * The source model for the route.
     *
     * @var \Hyde\Framework\Concerns\HydePage
     */
    protected HydePage $sourceModel;

    /**
     * The unique route key for the route.
     *
     * @var string The route key. Generally <output-directory/slug>.
     */
    protected string $routeKey;

    protected string $sourcePath;
    protected string $outputPath;
    protected string $uriPath;

    /**
     * Construct a new Route instance for the given page model.
     *
     * @param  \Hyde\Framework\Concerns\HydePage  $page
     */
    public function __construct(HydePage $page)
    {
        $this->sourceModel = $page;
        $this->routeKey = $page->getRouteKey();
        $this->sourcePath = $page->getSourcePath();
        $this->outputPath = $page->getOutputPath();
        $this->uriPath = $page->getLink();
    }

    /**
     * Cast a route object into a string that can be used in a href attribute.
     * Should be the same as getLink().
     */
    public function __toString(): string
    {
        return $this->getLink();
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'routeKey' => $this->routeKey,
            'sourcePath' => $this->sourcePath,
            'outputPath' => $this->outputPath,
            'sourceModel' => $this->sourceModel::class,
        ];
    }

    /**
     * Resolve a site web link to the file, using pretty URLs if enabled.
     *
     * @return string Relative URL path to the route site file.
     */
    public function getLink(): string
    {
        return Hyde::relativeLink($this->uriPath);
    }

    /**
     * Get the page type for the route.
     *
     * @return class-string<\Hyde\Framework\Concerns\HydePage>
     */
    public function getPageType(): string
    {
        return $this->sourceModel::class;
    }

    /**
     * Get the source model for the route.
     *
     * @return \Hyde\Framework\Concerns\HydePage
     */
    public function getSourceModel(): HydePage
    {
        return $this->sourceModel;
    }

    /**
     * Get the unique route key for the route.
     *
     * @return string The route key. Generally <output-directory/slug>.
     */
    public function getRouteKey(): string
    {
        return $this->routeKey;
    }

    /**
     * Get the path to the source file.
     *
     * @return string Path relative to the root of the project.
     */
    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    /**
     * Get the path to the output file.
     *
     * @return string Path relative to the site output directory.
     */
    public function getOutputPath(): string
    {
        return $this->outputPath;
    }

    /**
     * Get the qualified URL for the route, using pretty URLs if enabled.
     *
     * @return string Fully qualified URL using the configured base URL.
     */
    public function getQualifiedUrl(): string
    {
        return Hyde::url($this->outputPath);
    }

    /**
     * Determine if the route instance matches another route or route key.
     *
     * @param \Hyde\Routing\Route|string $route A route instance or route key string
     * @return bool
     */
    public function is(Route|string $route): bool
    {
        if ($route instanceof Route) {
            return $this->getRouteKey() === $route->getRouteKey();
        }

        return $this->getRouteKey() === $route;
    }

    /**
     * @inheritDoc
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return Router::$name(...$arguments);
    }
}
