<?php

declare(strict_types=1);

namespace Hyde\Facades;

use Hyde\Hyde;
use Hyde\Pages\MarkdownPost;
use Hyde\Pages\DocumentationPage;
use Hyde\Support\Concerns\Serializable;
use Hyde\Support\Contracts\SerializableContract;
use Hyde\Framework\Concerns\Internal\MockableFeatures;

use function extension_loaded;
use function in_array;
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
     * The features that are enabled.
     *
     * @var array<string, bool>
     */
    protected array $features = [];

    public function __construct()
    {
        $this->boot();
    }

    /** @experimental This method may change before its release. */
    public function getFeatures(): array
    {
        return $this->features;
    }

    /**
     * Determine if the given specified is enabled.
     *
     * @todo Rename to has() and add new enabled method to get just the enabled options array, and another called options/status/similar to get all options with their status.
     */
    public static function enabled(string $feature): bool
    {
        return Hyde::features()->getFeatures()[$feature] ?? false;
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

    public static function htmlPages(): string
    {
        return 'html-pages';
    }

    public static function bladePages(): string
    {
        return 'blade-pages';
    }

    public static function markdownPages(): string
    {
        return 'markdown-pages';
    }

    public static function markdownPosts(): string
    {
        return 'markdown-posts';
    }

    public static function documentationPages(): string
    {
        return 'documentation-pages';
    }

    public static function documentationSearch(): string
    {
        return 'documentation-search';
    }

    public static function darkmode(): string
    {
        return 'darkmode';
    }

    public static function torchlight(): string
    {
        return 'torchlight';
    }

    // ====================================================
    // Dynamic features that in addition to being enabled
    // in the config file, require preconditions to be met.
    // ====================================================

    /**
     * Can a sitemap be generated?
     */
    public static function hasSitemap(): bool
    {
        return Hyde::hasSiteUrl()
            && Config::getBool('hyde.generate_sitemap', true)
            && extension_loaded('simplexml');
    }

    /**
     * Can an RSS feed be generated?
     */
    public static function hasRss(): bool
    {
        return Hyde::hasSiteUrl()
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
        return $this->features;
    }

    /** @return array<string> */
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

    protected function boot(): void
    {
        $options = static::getDefaultOptions();

        $enabled = [];

        // Set all default features to false
        foreach ($options as $feature) {
            $enabled[$feature] = false;
        }

        // Set all features to true if they are enabled in the config file
        foreach ($this->getConfiguredFeatures() as $feature) {
            if (in_array($feature, $options)) {
                $enabled[$feature] = true;
            }
        }

        $this->features = $enabled;
    }

    /** @return array<string> */
    protected function getConfiguredFeatures(): array
    {
        return Config::getArray('hyde.features', static::getDefaultOptions());
    }
}
