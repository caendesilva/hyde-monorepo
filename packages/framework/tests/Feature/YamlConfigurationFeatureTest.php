<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Hyde;
use ReflectionClass;
use Hyde\Enums\Feature;
use Hyde\Testing\TestCase;
use Hyde\Facades\Features;
use Illuminate\Support\Env;
use Hyde\Pages\MarkdownPage;
use Hyde\Framework\Features\Navigation\NavigationItem;
use Hyde\Framework\Features\Blogging\Models\PostAuthor;
use Hyde\Framework\Features\Navigation\MainNavigationMenu;
use Hyde\Framework\Exceptions\InvalidConfigurationException;
use Hyde\Framework\Features\Navigation\NavigationMenuGenerator;

/**
 * Test the Yaml configuration feature.
 *
 * @see \Hyde\Framework\Testing\Feature\HighLevelYamlConfigurationFeatureTest
 *
 * @covers \Hyde\Foundation\Internal\LoadYamlConfiguration
 * @covers \Hyde\Foundation\Internal\LoadYamlEnvironmentVariables
 * @covers \Hyde\Foundation\Internal\YamlConfigurationRepository
 */
class YamlConfigurationFeatureTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (file_exists('hyde.yml')) {
            // Clean up if a test failed to clean up after itself.
            unlink('hyde.yml');
        }
    }

    protected function tearDown(): void
    {
        $this->clearEnvVars();

        parent::tearDown();
    }

    public function testCanDefineHydeConfigSettingsInHydeYmlFile()
    {
        $this->file('hyde.yml', <<<'YAML'
        name: Test
        url: "http://localhost"
        pretty_urls: false
        generate_sitemap: true
        rss:
          enabled: true
          filename: feed.xml
          description: Test RSS Feed
        language: en
        output_directory: _site
        YAML);
        $this->runBootstrappers();

        $this->assertSame('Test', config('hyde.name'));
        $this->assertSame('http://localhost', config('hyde.url'));
        $this->assertSame('feed.xml', config('hyde.rss.filename'));
        $this->assertSame('Test RSS Feed', config('hyde.rss.description'));
        $this->assertSame('en', config('hyde.language'));
        $this->assertSame('_site', config('hyde.output_directory'));
        $this->assertTrue(config('hyde.generate_sitemap'));
        $this->assertTrue(config('hyde.rss.enabled'));
        $this->assertFalse(config('hyde.pretty_urls'));
    }

    public function testCanDefineMultipleConfigSettingsInHydeYmlFile()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
            name: Test
            url: "http://localhost"
        docs:
            sidebar:
                header: "My Docs"
        YAML);

        $this->runBootstrappers();

        $this->assertSame('Test', config('hyde.name'));
        $this->assertSame('http://localhost', config('hyde.url'));
        $this->assertSame('My Docs', config('docs.sidebar.header'));
    }

    public function testBootstrapperAppliesYamlConfigurationWhenPresent()
    {
        $this->file('hyde.yml', 'name: Foo');
        $this->runBootstrappers();

        $this->assertSame('Foo', config('hyde.name'));
    }

    public function testChangesInYamlFileOverrideChangesInHydeConfig()
    {
        $this->file('hyde.yml', 'name: Foo');
        $this->runBootstrappers();

        $this->assertSame('Foo', config('hyde.name'));
    }

    public function testChangesInYamlFileOverrideChangesInHydeConfigWhenUsingYamlExtension()
    {
        $this->file('hyde.yaml', 'name: Foo');
        $this->runBootstrappers();

        $this->assertSame('Foo', config('hyde.name'));
    }

    public function testServiceGracefullyHandlesMissingFile()
    {
        $this->runBootstrappers();

        $this->assertSame('HydePHP', config('hyde.name'));
    }

    public function testServiceGracefullyHandlesEmptyFile()
    {
        $this->file('hyde.yml', '');
        $this->runBootstrappers();

        $this->assertSame('HydePHP', config('hyde.name'));
    }

    public function testCanAddArbitraryConfigKeys()
    {
        $this->file('hyde.yml', 'foo: bar');
        $this->runBootstrappers();

        $this->assertSame('bar', config('hyde.foo'));
    }

    public function testConfigurationOptionsAreMerged()
    {
        $this->file('hyde.yml', 'baz: hat');
        $this->runBootstrappers(['hyde' => [
            'foo' => 'bar',
            'baz' => 'qux',
        ]]);

        $this->assertSame('bar', config('hyde.foo'));
    }

    public function testCanAddConfigurationOptionsInNamespacedArray()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
          name: HydePHP
          foo: bar
          bar:
            baz: qux
        YAML);

        $this->runBootstrappers();

        $this->assertSame('HydePHP', config('hyde.name'));
        $this->assertSame('bar', config('hyde.foo'));
        $this->assertSame('qux', config('hyde.bar.baz'));
    }

    public function testCanAddArbitraryNamespacedData()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
          some: thing
        foo:
          bar: baz
        YAML);

        $this->runBootstrappers();

        $this->assertSame('baz', config('foo.bar'));
    }

    public function testAdditionalNamespacesRequireTheHydeNamespaceToBePresent()
    {
        $this->file('hyde.yml', <<<'YAML'
        foo:
          bar: baz
        YAML);

        $this->runBootstrappers();

        $this->assertNull(config('foo.bar'));
    }

    public function testAdditionalNamespacesRequiresHydeNamespaceToBeTheFirstEntry()
    {
        $this->file('hyde.yml', <<<'YAML'
        foo:
          bar: baz
        hyde:
          some: thing
        YAML);

        $this->runBootstrappers();

        $this->assertNull(config('foo.bar'));
    }

    public function testHydeNamespaceCanBeEmpty()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
        foo:
          bar: baz
        YAML);

        $this->runBootstrappers();

        $this->assertSame('baz', config('foo.bar'));
    }

    public function testHydeNamespaceCanBeNull()
    {
        // This is essentially the same as the empty state test above, at least according to the YAML spec.
        $this->file('hyde.yml', <<<'YAML'
        hyde: null
        foo:
          bar: baz
        YAML);

        $this->runBootstrappers();

        $this->assertSame('baz', config('foo.bar'));
    }

    public function testHydeNamespaceCanBlank()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde: ''
        foo:
          bar: baz
        YAML);

        $this->runBootstrappers();

        $this->assertSame('baz', config('foo.bar'));
    }

    public function testDotNotationCanBeUsed()
    {
        $this->file('hyde.yml', <<<'YAML'
        foo.bar.baz: qux
        YAML);

        $this->runBootstrappers();

        $this->assertSame(['bar' => ['baz' => 'qux']], config('hyde.foo'));
        $this->assertSame('qux', config('hyde.foo.bar.baz'));
    }

    public function testDotNotationCanBeUsedWithNamespaces()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
            foo.bar.baz: qux
        one:
            foo:
                bar:
                    baz: qux
        two:
            foo.bar.baz: qux
        YAML);

        $this->runBootstrappers();

        $expected = ['bar' => ['baz' => 'qux']];

        $this->assertSame($expected, config('hyde.foo'));
        $this->assertSame($expected, config('one.foo'));
        $this->assertSame($expected, config('two.foo'));
    }

    public function testSettingSiteNameSetsEnvVars()
    {
        $this->assertSame('HydePHP', config('hyde.name'));

        // Assert that the environment variables are not set.
        $this->assertSame([
            'env' => null,
            'Env::get' => null,
            'getenv' => false,
            '$_ENV' => null,
            '$_SERVER' => null,
        ], $this->envVars());

        $this->file('hyde.yml', <<<'YAML'
        name: Environment Example
        YAML);

        $this->runBootstrappers();

        // Assert that the environment variables are set.
        $this->assertSame([
            'env' => 'Environment Example',
            'Env::get' => 'Environment Example',
            'getenv' => 'Environment Example',
            '$_ENV' => 'Environment Example',
            '$_SERVER' => 'Environment Example',
        ], $this->envVars());

        $this->assertSame('Environment Example', config('hyde.name'));
    }

    public function testSettingSiteNameSetsSidebarHeader()
    {
        $this->file('hyde.yml', <<<'YAML'
        name: Root Example
        YAML);

        $this->runBootstrappers();

        $this->assertSame('Root Example Docs', config('docs.sidebar.header'));
    }

    public function testSettingSiteNameSetsSidebarHeaderWhenUsingHydeNamespace()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
            name: Hyde Example
        YAML);

        $this->runBootstrappers();

        $this->assertSame('Hyde Example Docs', config('docs.sidebar.header'));
    }

    public function testSettingSiteNameSetsSidebarHeaderUnlessAlreadySpecifiedInYamlConfig()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
            name: Example
        docs:
            sidebar:
                header: Custom
        YAML);

        $this->runBootstrappers();

        $this->assertSame('Custom', config('docs.sidebar.header'));
    }

    public function testSettingSiteNameSetsSidebarHeaderUnlessAlreadySpecifiedInStandardConfig()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
            name: Example
        YAML);

        $this->runBootstrappers(['docs.sidebar.header' => 'Custom']);

        $this->assertSame('Custom', config('docs.sidebar.header'));
    }

    public function testSettingSiteNameSetsRssFeedSiteName()
    {
        $this->file('hyde.yml', <<<'YAML'
        name: Example
        YAML);

        $this->runBootstrappers();

        $this->assertSame('Example RSS Feed', config('hyde.rss.description'));
    }

    public function testSettingSiteNameSetsRssFeedSiteNameWhenUsingHydeNamespace()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
            name: Example
        YAML);

        $this->runBootstrappers();

        $this->assertSame('Example RSS Feed', config('hyde.rss.description'));
    }

    public function testSettingSiteNameSetsRssFeedSiteNameUnlessAlreadySpecifiedInYamlConfig()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
            name: Example
            rss:
                description: Custom
        YAML);

        $this->runBootstrappers();

        $this->assertSame('Custom', config('hyde.rss.description'));
    }

    public function testSettingSiteNameSetsRssFeedSiteNameUnlessAlreadySpecifiedInStandardConfig()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
            name: Example
        YAML);

        $this->runBootstrappers(['hyde.rss.description' => 'Custom']);

        $this->assertSame('Custom', config('hyde.rss.description'));
    }

    public function testCanSetAuthorsInTheYamlConfig()
    {
        $this->file('hyde.yml', <<<'YAML'
        authors:
          username1:
            name: 'User 1'
            bio: 'Bio of user 1'
            website: 'https://user1.com'
            socials:
              twitter: '@user1'
              github: 'user1'
        
          username2:
            name: 'User 2'
            bio: 'Bio of user 2'
            socials:
              twitter: '@user2'
              github: 'user2'
        
          test:
            name: 'Test user'
            bio: 'Bio of test user' # TODO: support 'biography'
            website: 'https://test.com'
        YAML);

        $this->runBootstrappers();

        $authors = config('hyde.authors');

        $this->assertCount(3, $authors);
        $this->assertContainsOnlyInstancesOf(PostAuthor::class, $authors);
        $this->assertSame('User 1', $authors['username1']->name);
        $this->assertSame('User 2', $authors['username2']->name);
        $this->assertSame('Test user', $authors['test']->name);

        $this->assertSame('Bio of user 1', $authors['username1']->bio);
        $this->assertSame('Bio of user 2', $authors['username2']->bio);
        $this->assertSame('Bio of test user', $authors['test']->bio);

        $this->assertSame('https://user1.com', $authors['username1']->website);
        $this->assertNull($authors['username2']->website);
        $this->assertSame('https://test.com', $authors['test']->website);

        $this->assertSame(['twitter' => '@user1', 'github' => 'user1'], $authors['username1']->socials);
        $this->assertSame(['twitter' => '@user2', 'github' => 'user2'], $authors['username2']->socials);
        $this->assertNull($authors['test']->socials);
    }

    public function testTypeErrorsInAuthorsYamlConfigAreRethrownMoreHelpfully()
    {
        $exceptionThrown = false;

        file_put_contents('hyde.yml', <<<'YAML'
        authors:
          wrong:
            name: false
        YAML);

        try {
            $this->runBootstrappers();
        } catch (InvalidConfigurationException $exception) {
            $exceptionThrown = true;
            $this->assertSame('Invalid author configuration detected in the YAML config file. Please double check the syntax.', $exception->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'Failed asserting that the exception was thrown.');
        unlink('hyde.yml');
    }

    public function testCanSetCustomNavigationItemsInTheYamlConfig()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
          navigation:
            custom:
              - destination: 'https://example.com'
                label: 'Example'
                priority: 100
              - destination: 'about'
                label: 'About Us'
                priority: 200
              - destination: 'contact'
                label: 'Contact'
                priority: 300
        YAML);

        $this->runBootstrappers();

        $configItems = config('hyde.navigation.custom');

        $this->assertSame([
            [
                'destination' => 'https://example.com',
                'label' => 'Example',
                'priority' => 100,
            ], [
                'destination' => 'about',
                'label' => 'About Us',
                'priority' => 200,
            ], [
                'destination' => 'contact',
                'label' => 'Contact',
                'priority' => 300,
            ],
        ], $configItems);

        /** @var NavigationItem[] $navigationItems */
        $navigationItems = NavigationMenuGenerator::handle(MainNavigationMenu::class)->getItems()->all();

        $this->assertCount(4, $navigationItems);
        $this->assertContainsOnlyInstancesOf(NavigationItem::class, $navigationItems);

        $this->assertSame('index.html', $navigationItems[0]->getLink());
        $this->assertSame('Home', $navigationItems[0]->getLabel());
        $this->assertSame(0, $navigationItems[0]->getPriority());

        $this->assertSame('https://example.com', $navigationItems[1]->getLink());
        $this->assertSame('Example', $navigationItems[1]->getLabel());
        $this->assertSame(100, $navigationItems[1]->getPriority());

        $this->assertSame('about', $navigationItems[2]->getLink());
        $this->assertSame('About Us', $navigationItems[2]->getLabel());
        $this->assertSame(200, $navigationItems[2]->getPriority());

        $this->assertSame('contact', $navigationItems[3]->getLink());
        $this->assertSame('Contact', $navigationItems[3]->getLabel());
        $this->assertSame(300, $navigationItems[3]->getPriority());
    }

    public function testCanSetAttributesInNavigationItemsInTheYamlConfig()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
          navigation:
            custom:
              - destination: 'https://example.com'
                label: 'Example'
                priority: 100
                attributes:
                  class: 'example'
              - destination: 'about'
                label: 'About Us'
                priority: 200
                attributes:
                  class: 'about'
                  id: 'about'
              - destination: 'contact'
                label: 'Contact'
                priority: 300
                attributes:
                   target: '_blank'
                   rel: 'noopener noreferrer'
                   foo: 'bar'
        YAML);

        $this->runBootstrappers();

        $configItems = config('hyde.navigation.custom');

        $this->assertSame([
            [
                'destination' => 'https://example.com',
                'label' => 'Example',
                'priority' => 100,
                'attributes' => ['class' => 'example'],
            ], [
                'destination' => 'about',
                'label' => 'About Us',
                'priority' => 200,
                'attributes' => ['class' => 'about', 'id' => 'about'],
            ], [
                'destination' => 'contact',
                'label' => 'Contact',
                'priority' => 300,
                'attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer', 'foo' => 'bar'],
            ],
        ], $configItems);

        /** @var NavigationItem[] $navigationItems */
        $navigationItems = NavigationMenuGenerator::handle(MainNavigationMenu::class)->getItems()->all();

        $this->assertCount(4, $navigationItems);
        $this->assertContainsOnlyInstancesOf(NavigationItem::class, $navigationItems);

        $this->assertSame([], $navigationItems[0]->getExtraAttributes());
        $this->assertSame(['class' => 'example'], $navigationItems[1]->getExtraAttributes());
        $this->assertSame(['class' => 'about', 'id' => 'about'], $navigationItems[2]->getExtraAttributes());
        $this->assertSame(['target' => '_blank', 'rel' => 'noopener noreferrer', 'foo' => 'bar'], $navigationItems[3]->getExtraAttributes());
    }

    public function testOnlyNeedToAddDestinationToYamlConfiguredNavigationItems()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
          navigation:
            custom:
              - destination: 'about.html'
        YAML);

        $this->runBootstrappers();

        $configItems = config('hyde.navigation.custom');

        $this->assertSame([
            [
                'destination' => 'about.html',
            ],
        ], $configItems);

        /** @var NavigationItem[] $navigationItems */
        $navigationItems = NavigationMenuGenerator::handle(MainNavigationMenu::class)->getItems()->all();

        $this->assertCount(2, $navigationItems);
        $this->assertContainsOnlyInstancesOf(NavigationItem::class, $navigationItems);

        $this->assertSame('index.html', $navigationItems[0]->getLink());
        $this->assertSame('Home', $navigationItems[0]->getLabel());
        $this->assertSame(0, $navigationItems[0]->getPriority());

        $this->assertSame('about.html', $navigationItems[1]->getLink());
        $this->assertSame('about.html', $navigationItems[1]->getLabel()); // The label is automatically set to the destination.
        $this->assertSame(500, $navigationItems[1]->getPriority());
    }

    public function testNavigationItemsInTheYamlConfigCanBeResolvedToRoutes()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
          navigation:
            custom:
              - destination: 'about'
        YAML);

        $this->runBootstrappers();

        Hyde::routes()->addRoute((new MarkdownPage('about', ['title' => 'About Us', 'navigation' => ['priority' => 250]]))->getRoute());

        $navigationItems = NavigationMenuGenerator::handle(MainNavigationMenu::class)->getItems()->all();

        // The route is already automatically added to the navigation menu, so we'll have two of it.
        $this->assertCount(3, $navigationItems);
        $this->assertContainsOnlyInstancesOf(NavigationItem::class, $navigationItems);

        $this->assertEquals($navigationItems[1], $navigationItems[2]);

        $this->assertSame('about.html', $navigationItems[1]->getLink());
        $this->assertSame('About Us', $navigationItems[1]->getLabel());
        $this->assertSame(250, $navigationItems[1]->getPriority());
    }

    public function testTypeErrorsInNavigationYamlConfigAreRethrownMoreHelpfully()
    {
        $exceptionThrown = false;

        file_put_contents('hyde.yml', <<<'YAML'
        hyde:
          navigation:
            custom:
              - destination: false
        YAML);

        try {
            $this->runBootstrappers();
            NavigationMenuGenerator::handle(MainNavigationMenu::class)->getItems()->all();
        } catch (InvalidConfigurationException $exception) {
            $exceptionThrown = true;
            $this->assertSame('Invalid navigation item configuration detected the configuration file. Please double check the syntax.', $exception->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'Failed asserting that the exception was thrown.');
        unlink('hyde.yml');
    }

    public function testMustAddDestinationToYamlConfiguredNavigationItems()
    {
        $exceptionThrown = false;

        file_put_contents('hyde.yml', <<<'YAML'
        hyde:
          navigation:
            custom:
              - label: 'About Us'
        YAML);

        try {
            $this->runBootstrappers();
            NavigationMenuGenerator::handle(MainNavigationMenu::class)->getItems()->all();
        } catch (InvalidConfigurationException $exception) {
            $exceptionThrown = true;
            $this->assertSame('Invalid navigation item configuration detected the configuration file. Please double check the syntax.', $exception->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'Failed asserting that the exception was thrown.');
        unlink('hyde.yml');
    }

    public function testAddingExtraYamlNavigationItemFieldsThrowsAnException()
    {
        $exceptionThrown = false;

        file_put_contents('hyde.yml', <<<'YAML'
        hyde:
          navigation:
            custom:
              - destination: 'about'
                extra: 'field'
        YAML);

        try {
            $this->runBootstrappers();
            NavigationMenuGenerator::handle(MainNavigationMenu::class)->getItems()->all();
        } catch (InvalidConfigurationException $exception) {
            $exceptionThrown = true;
            $this->assertSame('Invalid navigation item configuration detected the configuration file. Please double check the syntax.', $exception->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'Failed asserting that the exception was thrown.');
        unlink('hyde.yml');
    }

    public function testCanSpecifyFeaturesInYamlConfiguration()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
          features:
            - html-pages
            - markdown-posts
            - blade-pages
            - markdown-pages
            - documentation-pages
        YAML);

        $this->runBootstrappers();

        $expectedFeatures = [
            Feature::HtmlPages,
            Feature::MarkdownPosts,
            Feature::BladePages,
            Feature::MarkdownPages,
            Feature::DocumentationPages,
        ];

        // Test that the Features facade methods return the correct values
        $this->assertTrue(Features::hasHtmlPages());
        $this->assertTrue(Features::hasMarkdownPosts());
        $this->assertTrue(Features::hasBladePages());
        $this->assertTrue(Features::hasMarkdownPages());
        $this->assertTrue(Features::hasDocumentationPages());
        $this->assertFalse(Features::hasDarkmode());
        $this->assertFalse(Features::hasDocumentationSearch());
        $this->assertFalse(Features::hasTorchlight());

        // Test that a disabled feature returns false
        $this->assertFalse(Features::has(Feature::Darkmode));

        // Use reflection to access the protected features property
        $reflection = new ReflectionClass(Hyde::features());
        $featuresProperty = $reflection->getProperty('features');
        $actualFeatures = $featuresProperty->getValue(Hyde::features());

        $this->assertSame($expectedFeatures, $actualFeatures);
    }

    public function testCanSpecifyFeaturesInYamlConfigurationWithUnderscores()
    {
        $this->file('hyde.yml', <<<'YAML'
        hyde:
          features:
            - html_pages
            - markdown_posts
            - blade_pages
            - markdown_pages
            - documentation_pages
        YAML);

        $this->runBootstrappers();

        $expectedFeatures = [
            Feature::HtmlPages,
            Feature::MarkdownPosts,
            Feature::BladePages,
            Feature::MarkdownPages,
            Feature::DocumentationPages,
        ];

        // Use reflection to access the protected features property
        $reflection = new ReflectionClass(Hyde::features());
        $featuresProperty = $reflection->getProperty('features');
        $actualFeatures = $featuresProperty->getValue(Hyde::features());

        $this->assertSame($expectedFeatures, $actualFeatures);
    }

    public function testExceptionIsThrownWhenFeatureIsNotDefined()
    {
        $exceptionThrown = false;

        file_put_contents('hyde.yml', <<<'YAML'
        hyde:
          features:
            - not-a-feature
        YAML);

        try {
            $this->runBootstrappers();
        } catch (InvalidConfigurationException $exception) {
            $exceptionThrown = true;
            $this->assertSame("Invalid feature 'not-a-feature' specified in the YAML config file. (Feature::NotAFeature does not exist)", $exception->getMessage());
        }

        unlink('hyde.yml');

        $this->assertTrue($exceptionThrown, 'Failed asserting that the exception was thrown.');
    }

    protected function runBootstrappers(?array $withMergedConfig = null): void
    {
        $this->refreshApplication();

        if ($withMergedConfig !== null) {
            $this->app['config']->set($withMergedConfig);
        }
    }

    protected function clearEnvVars(): void
    {
        // Todo: Can we access loader? https://github.com/vlucas/phpdotenv/pull/107/files
        putenv('SITE_NAME');
        unset($_ENV['SITE_NAME'], $_SERVER['SITE_NAME']);
    }

    protected function envVars(): array
    {
        return [
            'env' => env('SITE_NAME'),
            'Env::get' => Env::get('SITE_NAME'),
            'getenv' => getenv('SITE_NAME'),
            '$_ENV' => $_ENV['SITE_NAME'] ?? null,
            '$_SERVER' => $_SERVER['SITE_NAME'] ?? null,
        ];
    }
}
