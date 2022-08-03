<?php

namespace Hyde\Framework\Models\Pages;

use Hyde\Framework\Concerns\HasTableOfContents;
use Hyde\Framework\Contracts\AbstractMarkdownPage;
use Hyde\Framework\Contracts\RouteContract;
use Hyde\Framework\Models\Route;

class DocumentationPage extends AbstractMarkdownPage
{
    use HasTableOfContents;

    public static string $sourceDirectory = '_docs';
    public static string $outputDirectory = 'docs';
    public static string $template = 'hyde::layouts/docs';

    /**
     * The sidebar category group, if any.
     */
    public ?string $category;

    /**
     * The path to the page relative to the configured `_docs` directory.
     * Generally only needed if the page is in a subdirectory.
     */
    public ?string $localPath;

    public function __construct(array $matter = [], string $body = '', string $title = '', string $slug = '', ?string $category = null, ?string $localPath = null)
    {
        parent::__construct($matter, $body, $title, $slug);
        $this->category = $category;
        $this->localPath = $localPath;
    }

    /** @inheritDoc */
    public function getSourcePath(): string
    {
        return is_null($this->localPath) ? parent::getSourcePath() : static::qualifyBasename($this->localPath);
    }

    /** @internal */
    public function getOnlineSourcePath(): string|false
    {
        if (config('docs.source_file_location_base') === null) {
            return false;
        }

        return trim(config('docs.source_file_location_base'), '/').'/'.$this->slug.'.md';
    }

    public static function home(): ?RouteContract
    {
        return Route::exists('docs/index') ? Route::get('docs/index') : null;
    }

    public static function hasTableOfContents(): bool
    {
        return config('docs.table_of_contents.enabled', true);
    }
}
