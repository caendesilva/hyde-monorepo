<?php  /** @noinspection PhpFullyQualifiedNameUsageInspection */

declare(strict_types=1);

/**
 * @internal This file belongs to the HydePHP development repository,
 * and is not covered by the backward compatibility promise,
 * as it is not versioned through any official releases.
 *
 * When using a good IDE, such as PhpStorm, this file will be used to provide
 * rich code completion for "magic" fields, such as global data and facades.
 */

// Global page variables

/** @var \Hyde\Pages\Concerns\HydePage $page The page being compiled/previewed */
$page = \Hyde\Support\Facades\Render::getPage();

/** @var \Hyde\Support\Models\Route $route The route for the page being compiled/previewed */
$route = \Hyde\Support\Facades\Render::getRoute();

/** @var string $routeKey The route key for the page being compiled/previewed */
$routeKey = \Hyde\Support\Facades\Render::getRouteKey();

// Variables available only to some page types

/** @var \Hyde\Framework\Features\Navigation\DocumentationSidebar $sidebar */
$sidebar = app('navigation.sidebar');

// Facades (aliased in app/config.php)

/** @mixin \Hyde\Foundation\HydeKernel */
class Hyde extends \Hyde\Hyde {}
class Site extends \Hyde\Facades\Site {}
class Meta extends \Hyde\Facades\Meta {}
class Asset extends \Hyde\Facades\Asset {}
class Author extends \Hyde\Facades\Author {}
class Features extends \Hyde\Facades\Features {}
class Config extends \Hyde\Facades\Config {}
class Vite extends \Hyde\Facades\Vite {}
/** @mixin \Illuminate\Filesystem\Filesystem */
class Filesystem extends \Hyde\Facades\Filesystem {}
class DataCollection extends \Hyde\Support\DataCollection {}
class Includes extends \Hyde\Support\Includes {}
/** @mixin \Hyde\Foundation\Kernel\RouteCollection */
class Routes extends \Hyde\Foundation\Facades\Routes {}

// Page classes (aliased in app/config.php)
class HtmlPage extends \Hyde\Pages\HtmlPage {}
class BladePage extends \Hyde\Pages\BladePage {}
class MarkdownPage extends \Hyde\Pages\MarkdownPage {}
class MarkdownPost extends \Hyde\Pages\MarkdownPost {}
class DocumentationPage extends \Hyde\Pages\DocumentationPage {}
