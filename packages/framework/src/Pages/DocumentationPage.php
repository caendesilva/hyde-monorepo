<?php

declare(strict_types=1);

namespace Hyde\Pages;

use Hyde\Facades\Config;
use Hyde\Foundation\Facades\Routes;
use Hyde\Pages\Concerns\BaseMarkdownPage;
use Hyde\Support\Models\Route;

use function trim;
use function sprintf;
use function Hyde\unslash;
use function basename;

/**
 * Page class for documentation pages.
 *
 * Documentation pages are stored in the _docs directory and using the .md extension.
 * The Markdown will be compiled to HTML using the documentation page layout to the _site/docs/ directory.
 *
 * @see https://hydephp.com/docs/1.x/documentation-pages
 */
class DocumentationPage extends BaseMarkdownPage
{
    public static string $sourceDirectory = '_docs';
    public static string $outputDirectory = 'docs';
    public static string $template = 'hyde::layouts/docs';

    public static function home(): ?Route
    {
        return Routes::get(static::homeRouteName());
    }

    public static function homeRouteName(): string
    {
        return unslash(static::baseRouteKey().'/index');
    }

    /** @see https://hydephp.com/docs/1.x/documentation-pages#automatic-edit-page-button */
    public function getOnlineSourcePath(): string|false
    {
        if (Config::getNullableString('docs.source_file_location_base') === null) {
            return false;
        }

        return sprintf('%s/%s.md', trim(Config::getString('docs.source_file_location_base'), '/'), $this->identifier);
    }

    /**
     * Get the route key for the page.
     *
     * If flattened outputs are enabled, this will use the identifier basename so nested pages are flattened.
     */
    public function getRouteKey(): string
    {
        return Config::getBool('docs.flattened_output_paths', true)
            ? unslash(static::outputDirectory().'/'.basename(parent::getRouteKey()))
            : parent::getRouteKey();
    }

    /**
     * Get the path where the compiled page will be saved.
     *
     * If flattened outputs are enabled, this will use the identifier basename so nested pages are flattened.
     */
    public function getOutputPath(): string
    {
        return Config::getBool('docs.flattened_output_paths', true)
            ? static::outputPath(basename($this->identifier))
            : parent::getOutputPath();
    }
}
