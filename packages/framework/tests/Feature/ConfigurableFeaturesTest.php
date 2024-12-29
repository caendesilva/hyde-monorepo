<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Hyde;
use Hyde\Facades\Features;
use Hyde\Testing\TestCase;
use Hyde\Enums\Feature;
use Illuminate\Support\Facades\Config;

use function config;

/**
 * @covers \Hyde\Facades\Features
 */
class ConfigurableFeaturesTest extends TestCase
{
    public function testHasDocumentationSearchReturnsFalseWhenFeatureIsNotEnabled()
    {
        $this->expectMethodReturnsFalse('hasDocumentationSearch');
    }

    public function testHasDarkmodeReturnsFalseWhenFeatureIsNotEnabled()
    {
        $this->expectMethodReturnsFalse('hasDarkmode');
    }

    public function testHasTorchlightReturnsFalseWhenFeatureIsNotEnabled()
    {
        $this->expectMethodReturnsFalse('hasTorchlight');
    }

    public function testHasRssReturnsFalseWhenFeatureIsNotEnabled()
    {
        $this->expectMethodReturnsFalse('hasRss');
    }

    public function testHasDarkmodeReturnsTrueWhenFeatureIsEnabled()
    {
        $this->expectMethodReturnsTrue('hasDarkmode');
    }

    public function testHasSitemapReturnsFalseWhenFeatureIsNotEnabled()
    {
        $this->expectMethodReturnsFalse('hasSitemap');
    }

    public function testCanGenerateSitemapHelperReturnsTrueIfHydeHasBaseUrl()
    {
        config(['hyde.url' => 'foo']);
        $this->assertTrue(Features::hasSitemap());
    }

    public function testCanGenerateSitemapHelperReturnsFalseIfHydeDoesNotHaveBaseUrl()
    {
        config(['hyde.url' => '']);
        $this->assertFalse(Features::hasSitemap());
    }

    public function testCanGenerateSitemapHelperReturnsFalseIfSitemapsAreDisabledInConfig()
    {
        $this->withSiteUrl();
        config(['hyde.generate_sitemap' => false]);
        $this->assertFalse(Features::hasSitemap());
    }

    public function testHasThemeToggleButtonsReturnsTrueWhenDarkmodeEnabledAndConfigTrue()
    {
        // Enable dark mode and set hyde.theme_toggle_buttons config option to true
        Features::mock('darkmode', true);
        config(['hyde.theme_toggle_buttons' => true]);

        $this->assertTrue(Features::hasThemeToggleButtons());
    }

    public function testHasThemeToggleButtonsReturnsFalseWhenDarkmodeDisabled()
    {
        // Disable dark mode
        Features::mock('darkmode', false);
        // It doesn't matter what the config value is here

        $this->assertFalse(Features::hasThemeToggleButtons());
    }

    public function testHasThemeToggleButtonsReturnsFalseWhenConfigFalse()
    {
        // Enable dark mode
        Features::mock('darkmode', true);
        // Set hyde.theme_toggle_buttons config option to false
        config(['hyde.theme_toggle_buttons' => false]);

        $this->assertFalse(Features::hasThemeToggleButtons());
    }

    public function testHasThemeToggleButtonsReturnsTrueWhenDarkmodeEnabledAndConfigNotSet()
    {
        // Enable dark mode
        Features::mock('darkmode', true);
        // Config option not set, default value assumed to be true

        $this->assertTrue(Features::hasThemeToggleButtons());
    }

    public function testToArrayMethodContainsAllSettings()
    {
        $this->assertSame([
            'html-pages' => true,
            'markdown-posts' => true,
            'blade-pages' => true,
            'markdown-pages' => true,
            'documentation-pages' => true,
            'darkmode' => true,
            'documentation-search' => true,
            'torchlight' => true,
        ], (new Features)->toArray());
    }

    public function testToArrayMethodContainsAllSettingsIncludingFalseValues()
    {
        config(['hyde.features' => [
            Feature::HtmlPages,
            Feature::MarkdownPosts,
            Feature::BladePages,
        ]]);

        $this->assertSame([
            'html-pages' => true,
            'markdown-posts' => true,
            'blade-pages' => true,
            'markdown-pages' => false,
            'documentation-pages' => false,
            'darkmode' => false,
            'documentation-search' => false,
            'torchlight' => false,
        ], (new Features)->toArray());
    }

    public function testSerializedClassState()
    {
        config(['hyde.features' => [
            Feature::HtmlPages,
            Feature::MarkdownPosts,
            Feature::BladePages,
        ]]);

        $this->assertSame(<<<'JSON'
        {
            "html-pages": true,
            "markdown-posts": true,
            "blade-pages": true,
            "markdown-pages": false,
            "documentation-pages": false,
            "darkmode": false,
            "documentation-search": false,
            "torchlight": false
        }
        JSON, (new Features)->toJson(JSON_PRETTY_PRINT));
    }

    public function testFeaturesCanBeMocked()
    {
        Features::mock('darkmode', true);
        $this->assertTrue(Features::hasDarkmode());

        Features::mock('darkmode', false);
        $this->assertFalse(Features::hasDarkmode());
    }

    public function testMultipleFeaturesCanBeMocked()
    {
        Features::mock('blade-pages', true);
        Features::mock('darkmode', true);

        $this->assertTrue(Features::hasBladePages());
        $this->assertTrue(Features::hasDarkmode());

        Features::mock('blade-pages', false);
        Features::mock('darkmode', false);

        $this->assertFalse(Features::hasBladePages());
        $this->assertFalse(Features::hasDarkmode());
    }

    public function testGetEnabledUsesDefaultOptionsByDefault()
    {
        $default = $this->defaultOptions();

        $this->assertSame($default, Features::enabled());
    }

    public function testGetEnabledUsesDefaultOptionsWhenConfigIsEmpty()
    {
        config(['hyde' => []]);

        $default = $this->defaultOptions();

        $this->assertSame($default, Features::enabled());
    }

    public function testGetEnabledUsesConfiguredOptions()
    {
        config(['hyde.features' => [
            Feature::HtmlPages,
            Feature::MarkdownPosts,
            Feature::BladePages,
        ]]);

        $this->assertSame([
            'html-pages',
            'markdown-posts',
            'blade-pages',
        ], Features::enabled());
    }

    public function testCannotUseArbitraryValuesInEnabledOptions()
    {
        $this->expectException(\TypeError::class); // Todo: Consider if we should handle this again by ignoring it, or throw with a more specific message

        $config = [
            Feature::HtmlPages,
            Feature::MarkdownPosts,
            Feature::BladePages,
            'foo',
        ];

        config(['hyde.features' => $config]);

        $this->assertSame([
            'html-pages',
            'markdown-posts',
            'blade-pages',
        ], Hyde::features()->enabled());
    }

    protected function defaultOptions(): array
    {
        return [
            'html-pages',
            'markdown-posts',
            'blade-pages',
            'markdown-pages',
            'documentation-pages',
            'darkmode',
            'documentation-search',
            'torchlight',
        ];
    }

    protected function expectMethodReturnsFalse(string $method): void
    {
        Config::set('hyde.features', []);

        $this->assertFalse(Features::$method(), "Method '$method' should return false when feature is not enabled");
    }

    protected function expectMethodReturnsTrue(string $method): void
    {
        $this->assertTrue(Features::$method(), "Method '$method' should return true when feature is enabled");
    }
}
