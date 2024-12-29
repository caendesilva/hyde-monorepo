<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Hyde;
use Hyde\Facades\Config;
use Hyde\Testing\TestCase;
use Illuminate\Support\Str;
use Hyde\Pages\DocumentationPage;
use Hyde\Framework\Features\Navigation\NavigationItem;
use Hyde\Framework\Features\Navigation\NavigationGroup;
use Hyde\Framework\Features\Navigation\DocumentationSidebar;
use Hyde\Framework\Features\Navigation\NavigationMenuGenerator;

/**
 * High level test for the feature that allows sidebar items to be sorted by filename prefix.
 *
 * It also works within sidebar groups, so that multiple groups can have the same prefix independent of other groups.
 *
 * @covers \Hyde\Framework\Features\Navigation\NumericalPageOrderingHelper
 * @covers \Hyde\Framework\Features\Navigation\DocumentationSidebar
 * @covers \Hyde\Framework\Factories\NavigationDataFactory
 * @covers \Hyde\Support\Models\RouteKey
 *
 * @see \Hyde\Framework\Testing\Unit\NumericalPageOrderingHelperUnitTest
 */
class NumericalPageOrderingHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->helper = new FilenamePrefixNavigationPriorityTestingHelper($this);

        Config::set('hyde.navigation.subdirectory_display', 'dropdown');

        // Todo: Replace kernel with mock class
        $this->withoutDefaultPages();
    }

    protected function tearDown(): void
    {
        $this->restoreDefaultPages();

        parent::tearDown();
    }

    public function testDocumentationPageSourceFilesHaveTheirNumericalPrefixTrimmedFromRouteKeys()
    {
        $this->file('_docs/01-readme.md');

        $identifier = '01-readme';

        // Assert it is discovered.
        $discovered = DocumentationPage::get($identifier);
        $this->assertNotNull($discovered, 'The page was not discovered.');

        // Assert it is parsable
        $parsed = DocumentationPage::parse($identifier);
        $this->assertNotNull($parsed, 'The page was not parsable.');

        // Sanity check
        $this->assertEquals($discovered, $parsed);

        $page = $discovered;

        // Assert identifier is the same.
        $this->assertSame($identifier, $page->getIdentifier());

        // Assert the route key is trimmed.
        $this->assertSame('docs/readme', $page->getRouteKey());

        // Assert route key dependents are trimmed.
        $this->assertSame('docs/readme.html', $page->getOutputPath());
    }

    public function testFlatSidebarNavigationOrdering()
    {
        $this->setUpSidebarFixture([
            '01-readme.md',
            '02-installation.md',
            '03-getting-started.md',
        ]);

        $this->assertSidebarOrder(['readme', 'installation', 'getting-started']);
    }

    public function testGroupedSidebarNavigationOrdering()
    {
        $this->setUpSidebarFixture([
            '01-readme.md',
            '02-installation.md',
            '03-getting-started.md',
            '04-introduction' => [
                '01-general.md',
                '02-resources.md',
                '03-requirements.md',
            ],
            '05-advanced' => [
                '01-features.md',
                '02-extensions.md',
                '03-configuration.md',
            ],
        ]);

        $this->assertSidebarOrder([
            'other' => ['readme', 'installation', 'getting-started'],
            'introduction' => ['general', 'resources', 'requirements'],
            'advanced' => ['features', 'extensions', 'configuration'],
        ]);
    }

    public function testDifferentPrefixSyntaxesOrdering()
    {
        $fixtures = [
            [
                '1-foo.md',
                '2-bar.md',
                '3-baz.md',
            ], [
                '01-foo.md',
                '02-bar.md',
                '03-baz.md',
            ], [
                '001-foo.md',
                '002-bar.md',
                '003-baz.md',
            ],
        ];

        foreach ($fixtures as $fixture) {
            $this->setupFixture($fixture);

            $this->assertOrder(['foo', 'bar', 'baz']);
        }

        foreach ($fixtures as $fixture) {
            $this->setupFixture(array_reverse($fixture));

            $this->assertOrder(['foo', 'bar', 'baz']);
        }
    }

    public function testOrderingWithDifferentFileExtensions()
    {
        $this->setupFixture([
            '01-foo.md',
            '02-bar.html',
            '03-baz.blade.php',
        ]);

        $this->assertOrder(['foo', 'bar', 'baz']);
    }

    public function testSidebarGroupPrioritiesCanBeSetWithNumericalPrefix()
    {
        $this->directory('_docs/03-getting-started');
        $this->file('_docs/03-getting-started/05-advanced.md');

        $page = DocumentationPage::parse('03-getting-started/05-advanced');
        $this->assertInstanceOf(DocumentationPage::class, $page);

        $this->assertSame('docs/advanced', $page->getRouteKey());
        $this->assertSame('docs/advanced.html', $page->getOutputPath());
        $this->assertSame('getting-started', $page->navigationMenuGroup());
    }

    public function testSidebarGroupPrioritiesCanBeSetWithNumericalPrefixWithoutFlattenedOutputPaths()
    {
        config(['docs.flattened_output_paths' => false]);

        $this->directory('_docs/03-getting-started');
        $this->file('_docs/03-getting-started/05-advanced.md');

        $page = DocumentationPage::parse('03-getting-started/05-advanced');
        $this->assertInstanceOf(DocumentationPage::class, $page);

        $this->assertSame('docs/getting-started/advanced', $page->getRouteKey());
        $this->assertSame('docs/getting-started/advanced.html', $page->getOutputPath());
        $this->assertSame('getting-started', $page->navigationMenuGroup());
    }

    protected function setUpSidebarFixture(array $files): self
    {
        return $this->setupFixture($files);
    }

    protected function setupFixture(array $files): self
    {
        $this->helper->setupFixture($files);

        return $this;
    }

    protected function assertSidebarOrder(array $expected): void
    {
        $this->assertOrder($expected);
    }

    protected function assertOrder(array $expected): void
    {
        $actual = $this->helper->createComparisonFormat();

        $this->assertSame($expected, $actual);
    }

    protected function arrayReverseRecursive(array $array): array
    {
        $reversed = array_reverse($array);

        foreach ($reversed as $key => $value) {
            if (is_array($value)) {
                $reversed[$key] = $this->arrayReverseRecursive($value);
            }
        }

        return $reversed;
    }
}

class FilenamePrefixNavigationPriorityTestingHelper
{
    protected NumericalPageOrderingHelperTest $test;

    public function __construct(NumericalPageOrderingHelperTest $test)
    {
        $this->test = $test;
    }

    public function setupFixture(array $files): void
    {
        foreach ($files as $key => $file) {
            $class = DocumentationPage::class;

            is_string($file)
                ? $this->setupFixtureItem($class, $file)
                : $this->setupNestedFixtureItems($file, $key, $class);
        }
    }

    protected function setupFixtureItem(string $class, string $file): void
    {
        $page = new $class(Str::before($file, '.'), [], $this->generateMarkdown($file));
        Hyde::pages()->addPage($page);
        Hyde::routes()->addRoute($page->getRoute());
    }

    protected function setupNestedFixtureItems(array $files, string $key, string $class): void
    {
        foreach ($files as $file) {
            $group = str($key)->after('-');
            $page = new $class($group.'/'.Str::before($file, '.'), [], $this->generateMarkdown($file));
            Hyde::pages()->addPage($page);
            Hyde::routes()->addRoute($page->getRoute());
        }
    }

    protected function generateMarkdown(string $file): string
    {
        return sprintf("# %s\n\nHello, world!\n", str($file)->after('-')->before('.')->ucfirst());
    }

    public function createComparisonFormat(): array
    {
        $type = DocumentationSidebar::class;
        $menu = NavigationMenuGenerator::handle($type);

        return $this->mapItemsToStrings($menu)->all();
    }

    protected function mapItemsToStrings(DocumentationSidebar $menu)
    {
        return $menu->getItems()->mapWithKeys(fn ($item, $key) => $item instanceof NavigationItem
            ? [$key => $this->formatRouteKey($item->getPage()->getRouteKey())]
            : [$item->getGroupKey() => $this->mapChildItems($item)]);
    }

    protected function mapChildItems(NavigationGroup $item)
    {
        return $item->getItems()->map(function (NavigationItem $item) {
            return basename($this->formatRouteKey($item->getPage()->getRouteKey()));
        })->all();
    }

    protected function formatRouteKey(string $routeKey): string
    {
        return Str::after($routeKey, 'docs/');
    }
}
