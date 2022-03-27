<?php

namespace Hyde\Framework\Services;

use Hyde\Framework\Hyde;
use Hyde\Framework\Models\BladePage;
use Hyde\Framework\Models\DocumentationPage;
use Hyde\Framework\Models\MarkdownPage;
use Hyde\Framework\Models\MarkdownPost;
use JetBrains\PhpStorm\Pure;

/**
 * Contains service methods to return helpful collections of arrays and lists.
 */
class CollectionService
{
    /**
     * Return an array of all the source markdown slugs of the specified model.
     * Array format is ['_relative/path.md' => 'path.md'].
     *
     * @param  string  $model
     * @return array|false array on success, false if the class was not found
     */
    public static function getSourceSlugsOfModels(string $model): array|false
    {
        if ($model == BladePage::class) {
            return self::getBladePageList();
        }

        if ($model == MarkdownPage::class) {
            return self::getMarkdownPageList();
        }

        if ($model == MarkdownPost::class) {
            return self::getMarkdownPostList();
        }

        if ($model == DocumentationPage::class) {
            return self::getDocumentationPageList();
        }

        return false;
    }

    /**
     * Get all the Blade files in the resources/views/vendor/hyde/pages directory.
     *
     * @return array
     */
    #[Pure]
    public static function getBladePageList(): array
    {
        $array = [];

        foreach (glob(Hyde::path('resources/views/pages/*.blade.php')) as $filepath) {
            $array[] = basename($filepath, '.blade.php');
        }

        return $array;
    }

    /**
     * Get all the Markdown files in the _pages directory.
     *
     * @return array
     */
    #[Pure]
    public static function getMarkdownPageList(): array
    {
        $array = [];

        foreach (glob(Hyde::path('_pages/*.md')) as $filepath) {
            $array[] = basename($filepath, '.md');
        }

        return $array;
    }

    /**
     * Get all the Markdown files in the _posts directory.
     *
     * @return array
     */
    #[Pure]
    public static function getMarkdownPostList(): array
    {
        $array = [];

        foreach (glob(Hyde::path('_posts/*.md')) as $filepath) {
            $array[] = basename($filepath, '.md');
        }

        return $array;
    }

    /**
     * Get all the Markdown files in the _docs directory.
     *
     * @return array
     */
    #[Pure]
    public static function getDocumentationPageList(): array
    {
        $array = [];

        foreach (glob(Hyde::path('_docs/*.md')) as $filepath) {
            $array[] = basename($filepath, '.md');
        }

        return $array;
    }
}
