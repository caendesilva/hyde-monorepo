<?php

declare(strict_types=1);

namespace Hyde\Facades;

use Hyde\Hyde;
use Illuminate\Support\Str;
use Hyde\Pages\MarkdownPost;
use Hyde\Pages\DocumentationPage;
use Hyde\Enums\Feature;
use Hyde\Support\Concerns\Serializable;
use Hyde\Support\Contracts\SerializableContract;
use Illuminate\Support\Arr;

use function collect;
use function array_filter;
use function extension_loaded;
use function in_array;
use function count;
use function app;

/**
 * Allows features to be enabled and disabled in a simple object-oriented manner.
 */
class Features implements SerializableContract
{
    use Serializable;

    /**
     * The features that are enabled.
     *
     * @var array<\Hyde\Enums\Feature>
     */
    protected array $features = [];

    public function __construct()
    {
        $this->features = Config::getArray('hyde.features', Feature::cases());
    }

    /**
     * Determine if the given specified is enabled.
     */
    public static function has(Feature $feature): bool
    {
        return in_array($feature, Hyde::features()->features);
    }

    /**
     * Get all enabled features.
     *
     * @return array<string>
     */
    public static function enabled(): array
    {
        return Arr::map(Hyde::features()->features, fn (Feature $feature): string => Str::kebab($feature->name));
    }

    public static function hasHtmlPages(): bool
    {
        return static::has(Feature::HtmlPages);
    }

    public static function hasBladePages(): bool
    {
        return static::has(Feature::BladePages);
    }

    public static function hasMarkdownPages(): bool
    {
        return static::has(Feature::MarkdownPages);
    }

    public static function hasMarkdownPosts(): bool
    {
        return static::has(Feature::MarkdownPosts);
    }

    public static function hasDocumentationPages(): bool
    {
        return static::has(Feature::DocumentationPages);
    }

    public static function hasDarkmode(): bool
    {
        return static::has(Feature::Darkmode);
    }

    public static function hasThemeToggleButtons(): bool
    {
        return static::hasDarkmode() && Config::getBool('hyde.theme_toggle_buttons', true);
    }

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
     * Should documentation search be enabled?
     */
    public static function hasDocumentationSearch(): bool
    {
        return static::has(Feature::DocumentationSearch)
            && static::hasDocumentationPages()
            && count(DocumentationPage::files()) > 0;
    }

    /**
     * Torchlight is by default enabled automatically when an API token
     * is set in the `.env` file but is disabled when running tests.
     */
    public static function hasTorchlight(): bool
    {
        return static::has(Feature::Torchlight)
            && (Config::getNullableString('torchlight.token') !== null)
            && (app('env') !== 'testing');
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
        return Arr::mapWithKeys(Feature::cases(), fn (Feature $feature): array => [
            Str::kebab($feature->name) => static::has($feature),
        ]);
    }

    /** @internal This method is not covered by the backward compatibility promise. */
    public static function mock(string $feature, bool $enabled = null): void
    {
        if ($enabled === true) {
            // Add the feature if it doesn't already exist.
            Hyde::features()->features[] = collect(Feature::cases())->firstOrFail(fn (Feature $search): bool => Str::kebab($search->name) === $feature);
        } else {
            // Remove the feature if it exists.
            Hyde::features()->features = array_filter(Hyde::features()->features, fn (Feature $search): bool => Str::kebab($search->name) !== $feature);
        }
    }
}
