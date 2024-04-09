<?php

declare(strict_types=1);

namespace Hyde\Facades;

use Hyde\Hyde;
use Hyde\Enums\Feature;
use Hyde\Pages\MarkdownPost;
use Hyde\Pages\DocumentationPage;
use Hyde\Support\Concerns\Serializable;
use Hyde\Support\Contracts\SerializableContract;
use Hyde\Framework\Concerns\Internal\MockableFeatures;
use Illuminate\Support\Str;

use function get_class_methods;
use function extension_loaded;
use function str_starts_with;
use function in_array;
use function collect;
use function substr;
use function count;
use function app;

/**
 * Allows features to be enabled and disabled in a simple object-oriented manner.
 *
 * @internal Until this class is split into a service/manager class, it should not be used outside of Hyde as the API is subject to change.
 *
 * @todo Split facade logic to service/manager class. (Initial and mock data could be set with boot/set methods)
 * Based entirely on Laravel Jetstream (License MIT)
 *
 * @see https://jetstream.laravel.com/
 */
class Features implements SerializableContract
{
    use Serializable;
    use MockableFeatures;

    /**
     * Determine if the given specified is enabled.
     */
    public static function enabled(Feature $feature): bool
    {
        return static::resolveMockedInstance($feature->value) ?? in_array(
            $feature, Config::getArray('hyde.features', static::getDefaultOptions())
        );
    }

    // ================================================
    // Determine if a given feature is enabled.
    // ================================================

    public static function hasHtmlPages(): bool
    {
        return static::enabled(static::htmlPages());
    }

    public static function hasBladePages(): bool
    {
        return static::enabled(static::bladePages());
    }

    public static function hasMarkdownPages(): bool
    {
        return static::enabled(static::markdownPages());
    }

    public static function hasMarkdownPosts(): bool
    {
        return static::enabled(static::markdownPosts());
    }

    public static function hasDocumentationPages(): bool
    {
        return static::enabled(static::documentationPages());
    }

    public static function hasDocumentationSearch(): bool
    {
        return static::enabled(static::documentationSearch())
            && static::hasDocumentationPages()
            && count(DocumentationPage::files()) > 0;
    }

    public static function hasDarkmode(): bool
    {
        return static::enabled(static::darkmode());
    }

    /**
     * Torchlight is by default enabled automatically when an API token
     * is set in the .env file but is disabled when running tests.
     */
    public static function hasTorchlight(): bool
    {
        return static::enabled(static::torchlight())
            && (Config::getNullableString('torchlight.token') !== null)
            && (app('env') !== 'testing');
    }

    // =================================================
    // Configure features to be used in the config file.
    // =================================================

    /**
     * @deprecated This method will be removed in v2.0. Please use `Feature::HtmlPages` instead.
     */
    public static function htmlPages(): Feature
    {
        return Feature::HtmlPages;
    }

    /**
     * @deprecated This method will be removed in v2.0. Please use `Feature::BladePages` instead.
     */
    public static function bladePages(): Feature
    {
        return Feature::BladePages;
    }

    /**
     * @deprecated This method will be removed in v2.0. Please use `Feature::MarkdownPages` instead.
     */
    public static function markdownPages(): Feature
    {
        return Feature::MarkdownPages;
    }

    /**
     * @deprecated This method will be removed in v2.0. Please use `Feature::MarkdownPosts` instead.
     */
    public static function markdownPosts(): Feature
    {
        return Feature::MarkdownPosts;
    }

    /**
     * @deprecated This method will be removed in v2.0. Please use `Feature::DocumentationPages` instead.
     */
    public static function documentationPages(): Feature
    {
        return Feature::DocumentationPages;
    }

    /**
     * @deprecated This method will be removed in v2.0. Please use `Feature::DocumentationSearch` instead.
     */
    public static function documentationSearch(): Feature
    {
        return Feature::DocumentationSearch;
    }

    /**
     * @deprecated This method will be removed in v2.0. Please use `Feature::Darkmode` instead.
     */
    public static function darkmode(): Feature
    {
        return Feature::Darkmode;
    }

    /**
     * @deprecated This method will be removed in v2.0. Please use `Feature::Torchlight` instead.
     */
    public static function torchlight(): Feature
    {
        return Feature::Torchlight;
    }

    // ====================================================
    // Dynamic features that in addition to being enabled
    // in the config file, require preconditions to be met.
    // ====================================================

    /** Can a sitemap be generated? */
    public static function sitemap(): bool
    {
        return static::resolveMockedInstance('sitemap') ?? Hyde::hasSiteUrl()
            && Config::getBool('hyde.generate_sitemap', true)
            && extension_loaded('simplexml');
    }

    /** Can an RSS feed be generated? */
    public static function rss(): bool
    {
        return static::resolveMockedInstance('rss') ?? Hyde::hasSiteUrl()
            && static::hasMarkdownPosts()
            && Config::getBool('hyde.rss.enabled', true)
            && extension_loaded('simplexml')
            && count(MarkdownPost::files()) > 0;
    }

    /**
     * Get an array representation of the features and their status.
     *
     * @return array<string, bool>
     *
     * @example ['html-pages' => true, 'markdown-pages' => false, ...]
     */
    public function toArray(): array
    {
        return collect(get_class_methods(static::class))
            ->filter(fn (string $method): bool => str_starts_with($method, 'has'))
            ->mapWithKeys(fn (string $method): array => [
                Str::kebab(substr($method, 3)) => static::{$method}(),
            ])->toArray();
    }

    protected static function getDefaultOptions(): array
    {
        return [
            // Page Modules
            static::htmlPages(),
            static::markdownPosts(),
            static::bladePages(),
            static::markdownPages(),
            static::documentationPages(),

            // Frontend Features
            static::darkmode(),
            static::documentationSearch(),

            // Integrations
            static::torchlight(),
        ];
    }
}
