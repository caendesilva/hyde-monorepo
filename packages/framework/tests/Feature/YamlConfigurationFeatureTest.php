<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Dotenv\Dotenv;
use Hyde\Testing\TestCase;
use Illuminate\Support\Env;
use Illuminate\Config\Repository;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;
use Dotenv\Repository\Adapter\PutenvAdapter;

use function env;
use function dump;
use function getenv;
use function putenv;
use function config;

/**
 * Test the Yaml configuration feature.
 *
 * @covers \Hyde\Foundation\Internal\LoadYamlConfiguration
 * @covers \Hyde\Foundation\Internal\LoadYamlEnvironmentVariables
 * @covers \Hyde\Foundation\Internal\YamlConfigurationRepository
 */
class YamlConfigurationFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        $this->clearEnvVars();
        /** Make sure that {@see \Dotenv\Repository\Adapter\ImmutableWriter::write} returns false as that will then leave the environment variable alone between tests. */
        Env::getRepository()->set('SITE_NAME', '__INIT__');
        $this->clearEnvVars();
        // Create a mutable dotenv repository.
        $builder = RepositoryBuilder::createWithNoAdapters();
        $builder = $builder->addAdapter(PutenvAdapter::class);
        $repository = $builder->make();

        // Set the repository for the Env class.
        ExtendEnv::setRepository($repository);
        $this->clearEnvVars();
        parent::setUp();
        app()->singleton(Env::class, fn () => ExtendEnv::class);
    }

    protected function tearDown(): void
    {
        ExtendEnv::clear();
        $this->clearEnvVars();

        parent::tearDown();
    }

    public function testInit()
    {
        $this->file('hyde.yml', '');
        $this->runBootstrappers();
        $this->assertTrue(true);
    }

    public function testCanDefineHydeConfigSettingsInHydeYmlFile()
    {
        dump('first test');

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
        $this->assertSame(false, config('hyde.pretty_urls'));
        $this->assertSame(true, config('hyde.generate_sitemap'));
        $this->assertSame(true, config('hyde.rss.enabled'));
        $this->assertSame('feed.xml', config('hyde.rss.filename'));
        $this->assertSame('Test RSS Feed', config('hyde.rss.description'));
        $this->assertSame('en', config('hyde.language'));
        $this->assertSame('_site', config('hyde.output_directory'));
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
            '$_ENV' => null,
            '$_SERVER' => null,
        ], $this->envVars());

        $this->assertSame('Environment Example', config('hyde.name'));
    }

    public function testSettingSiteNameSetsSidebarHeader()
    {
        $this->clearEnvVars();
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

    protected function runBootstrappers(?array $withMergedConfig = null): void
    {
        $this->refreshApplication();

        if ($withMergedConfig !== null) {
            $this->app['config']->set($withMergedConfig);
        }
    }

    protected function getExecConfig(): Repository
    {
        // Due to how environment data handling is hardcoded in so many places,
        // we can't reliably test these features, as we can't reset the testing
        // environment after each test. We thus need to run the code in a
        // separate process to ensure a clean slate. This means we lose
        // code coverage, but at least we can test the feature.

        $code = 'var_export(config()->all());';
        $output = shell_exec('php hyde tinker --execute="'.$code.'" exit;');

        // On the following, __set_state does not exist so we turn it into an array
        $output = str_replace('Hyde\Framework\Features\Metadata\Elements\MetadataElement::__set_state', 'collect', $output);
        $output = str_replace('Hyde\Framework\Features\Metadata\Elements\OpenGraphElement::__set_state', 'collect', $output);
        $output = str_replace('Hyde\Framework\Features\Blogging\Models\PostAuthor::__set_state', 'collect', $output);

        $config = eval('return '.$output.';');

        return new Repository($config);
    }

    protected function clearEnvVars(): void
    {
        // Can we access loader? https://github.com/vlucas/phpdotenv/pull/107/files
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

class ExtendEnv extends Env
{
    public static function setRepository(RepositoryInterface $repository): void
    {
        static::$repository = $repository;
    }

    public static function clear(): void
    {
        static::$repository = null;
    }
}
