<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Pages\MarkdownPage;
use Hyde\Testing\UnitTestCase;
use Hyde\Pages\DocumentationPage;
use Hyde\Markdown\Models\Markdown;
use Hyde\Markdown\Models\FrontMatter;
use Hyde\Framework\Factories\NavigationDataFactory;
use Hyde\Framework\Factories\Concerns\CoreDataObject;

/**
 * @covers \Hyde\Framework\Factories\NavigationDataFactory
 */
class NavigationDataFactoryUnitTest extends UnitTestCase
{
    protected function setUp(): void
    {
        self::resetKernel();
        self::mockConfig();
    }

    public function testSearchForPriorityInNavigationConfigForMarkdownPageWithKeyedConfig()
    {
        self::mockConfig(['hyde.navigation.order' => [
            'foo' => 15,
            'bar' => 10,
        ]]);

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject(routeKey: 'foo'));
        $this->assertSame(15, $factory->makePriority());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject(routeKey: 'bar'));
        $this->assertSame(10, $factory->makePriority());
    }

    public function testSearchForPriorityInNavigationConfigForMarkdownPageWithListConfig()
    {
        self::mockConfig(['hyde.navigation.order' => [
            'foo',
            'bar',
        ]]);

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject(routeKey: 'foo'));
        $this->assertSame(500, $factory->makePriority());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject(routeKey: 'bar'));
        $this->assertSame(501, $factory->makePriority());
    }

    public function testSearchForPriorityInNavigationConfigForMarkdownPageSupportsMixingKeyedAndListConfig()
    {
        self::mockConfig(['hyde.navigation.order' => [
            'foo',
            'bar' => 10,
            'baz',
        ]]);

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject(routeKey: 'foo'));
        $this->assertSame(500, $factory->makePriority());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject(routeKey: 'bar'));
        $this->assertSame(10, $factory->makePriority());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject(routeKey: 'baz'));
        $this->assertSame(501, $factory->makePriority());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject(routeKey: 'qux'));
        $this->assertSame(999, $factory->makePriority());
    }

    public function testSearchForPriorityInNavigationConfigForDocumentationPageWithListConfig()
    {
        self::mockConfig(['docs.sidebar.order' => [
            'foo' => 15,
            'bar' => 10,
        ]]);

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('foo', pageClass: DocumentationPage::class));
        $this->assertSame(15, $factory->makePriority());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('bar', pageClass: DocumentationPage::class));
        $this->assertSame(10, $factory->makePriority());
    }

    public function testSearchForPriorityInNavigationConfigForDocumentationPageWithKeyedConfig()
    {
        self::mockConfig(['docs.sidebar.order' => [
            'foo',
            'bar' => 10,
            'baz',
        ]]);

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('foo', pageClass: DocumentationPage::class));
        $this->assertSame(500, $factory->makePriority());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('bar', pageClass: DocumentationPage::class));
        $this->assertSame(10, $factory->makePriority());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('baz', pageClass: DocumentationPage::class));
        $this->assertSame(501, $factory->makePriority());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('qux', pageClass: DocumentationPage::class));
        $this->assertSame(999, $factory->makePriority());
    }

    public function testSearchForPriorityInNavigationConfigForDocumentationPageSupportsMixingKeyedAndListConfig()
    {
        self::mockConfig(['docs.sidebar.order' => [
            'foo',
            'bar' => 10,
            'baz',
        ]]);

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('foo', pageClass: DocumentationPage::class));
        $this->assertSame(500, $factory->makePriority());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('bar', pageClass: DocumentationPage::class));
        $this->assertSame(10, $factory->makePriority());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('baz', pageClass: DocumentationPage::class));
        $this->assertSame(501, $factory->makePriority());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('qux', pageClass: DocumentationPage::class));
        $this->assertSame(999, $factory->makePriority());
    }

    public function testRouteKeysCanBeUsedForDocumentationSidebarPriorities()
    {
        self::mockConfig(['docs.sidebar.order' => [
            'key/foo',
            'key/bar',
            'baz',
        ]]);

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('foo', routeKey: 'key/foo', pageClass: DocumentationPage::class));
        $this->assertSame(500, $factory->makePriority());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('bar', routeKey: 'key/bar', pageClass: DocumentationPage::class));
        $this->assertSame(501, $factory->makePriority());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('baz', routeKey: 'key', pageClass: DocumentationPage::class));
        $this->assertSame(502, $factory->makePriority());
    }

    public function testSearchForLabelInNavigationConfigForMarkdownPage()
    {
        self::mockConfig([
            'hyde.navigation.labels' => [
                'foo' => 'Foo Label',
                'bar' => 'Bar Label',
            ],
        ]);

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject(routeKey: 'foo'));
        $this->assertSame('Foo Label', $factory->makeLabel());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject(routeKey: 'bar'));
        $this->assertSame('Bar Label', $factory->makeLabel());
    }

    public function testSearchForLabelInSidebarConfigForDocumentationPage()
    {
        self::mockConfig([
            'docs.sidebar.labels' => [
                'foo' => 'Documentation Foo Label',
                'bar' => 'Documentation Bar Label',
            ],
        ]);

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject(routeKey: 'foo', pageClass: DocumentationPage::class));
        $this->assertSame('Documentation Foo Label', $factory->makeLabel());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject(routeKey: 'bar', pageClass: DocumentationPage::class));
        $this->assertSame('Documentation Bar Label', $factory->makeLabel());
    }

    public function testLabelFallbackToTitleIfNotDefinedInConfig()
    {
        self::mockConfig([
            'hyde.navigation.labels' => [],
            'docs.sidebar.labels' => [],
        ]);

        // Assuming the title fallback is correctly set in front matter or title property
        $frontMatter = new FrontMatter(['title' => 'Fallback Title']);
        $coreDataObject = new CoreDataObject($frontMatter, new Markdown(), MarkdownPage::class, '', '', '', 'undefinedKey');

        $factory = new NavigationConfigTestClass($coreDataObject);
        $this->assertSame('Fallback Title', $factory->makeLabel());
    }

    public function testPageIsHiddenBasedOnNavigationConfiguration()
    {
        self::mockConfig(['hyde.navigation.exclude' => ['hiddenPage']]);

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject(routeKey: 'hiddenPage'));
        $this->assertTrue($factory->makeHidden());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject(routeKey: 'visiblePage'));
        $this->assertFalse($factory->makeHidden());
    }

    public function testPageIsHiddenBasedOnSidebarConfigurationForDocumentationPage()
    {
        self::mockConfig(['docs.sidebar.exclude' => ['hiddenDocPage']]);

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('hiddenDocPage', pageClass: DocumentationPage::class));
        $this->assertTrue($factory->makeHidden());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('visibleDocPage', pageClass: DocumentationPage::class));
        $this->assertFalse($factory->makeHidden());
    }

    public function testSearchForHiddenInConfigsSelectsCorrectConfigurationBasedOnPageType()
    {
        self::mockConfig([
            'hyde.navigation.exclude' => ['hiddenPage'],
            'docs.sidebar.exclude' => ['hiddenDocPage'],
        ]);

        // Test for a Markdown page, should use navigation.exclude config
        $factory = new NavigationConfigTestClass($this->makeCoreDataObject(routeKey: 'hiddenPage'));
        $this->assertTrue($factory->makeHidden());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject(routeKey: 'visiblePage'));
        $this->assertFalse($factory->makeHidden());

        // Test for a Documentation page, should use docs.sidebar.exclude config
        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('hiddenDocPage', pageClass: DocumentationPage::class));
        $this->assertTrue($factory->makeHidden());

        $factory = new NavigationConfigTestClass($this->makeCoreDataObject('visibleDocPage', pageClass: DocumentationPage::class));
        $this->assertFalse($factory->makeHidden());
    }

    public function testMakeGroupUsesFrontMatterGroupIfSet()
    {
        $frontMatter = new FrontMatter(['navigation.group' => 'Test Group']);
        $coreDataObject = new CoreDataObject($frontMatter, new Markdown(), MarkdownPage::class, 'test.md', '', '', '');
        $factory = new NavigationConfigTestClass($coreDataObject);

        $this->assertSame('Test Group', $factory->makeGroup());
    }

    public function testMakeGroupUsesFrontMatterGroupIfSetRegardlessOfSubdirectoryConfiguration()
    {
        self::mockConfig(['hyde.navigation.subdirectory_display' => 'hidden']);

        $frontMatter = new FrontMatter(['navigation.group' => 'Test Group']);
        $coreDataObject = new CoreDataObject($frontMatter, new Markdown(), MarkdownPage::class, 'test.md', '', '', '');
        $factory = new NavigationConfigTestClass($coreDataObject);

        $this->assertSame('Test Group', $factory->makeGroup());
    }

    public function testMakeGroupDefaultsToNullIfFrontMatterGroupNotSetAndSubdirectoriesNotUsed()
    {
        self::mockConfig(['hyde.navigation.subdirectory_display' => 'hidden']);

        $coreDataObject = new CoreDataObject(new FrontMatter(), new Markdown(), MarkdownPage::class, 'test.md', '', '', '');
        $factory = new NavigationConfigTestClass($coreDataObject);

        $this->assertNull($factory->makeGroup());
    }

    public function testItExtractsPriorityFromNumericalFilenamePrefix()
    {
        $coreDataObject = $this->makeCoreDataObject('01-test.md', pageClass: DocumentationPage::class);
        $factory = new NavigationConfigTestClass($coreDataObject);

        $this->assertSame(1, $factory->makePriority());
    }

    public function testItExtractsPriorityFromNumericalFilenamePrefixWithKebabCaseSyntax()
    {
        $this->assertSame(1, (new NavigationConfigTestClass($this->makeCoreDataObject('01-foo.md', pageClass: DocumentationPage::class)))->makePriority());
        $this->assertSame(2, (new NavigationConfigTestClass($this->makeCoreDataObject('02-bar.md', pageClass: DocumentationPage::class)))->makePriority());
        $this->assertSame(3, (new NavigationConfigTestClass($this->makeCoreDataObject('03-baz.md', pageClass: DocumentationPage::class)))->makePriority());
    }

    public function testItExtractsPriorityFromNumericalFilenamePrefixWithSnakeCaseSyntax()
    {
        $this->assertSame(1, (new NavigationConfigTestClass($this->makeCoreDataObject('01_foo.md', pageClass: DocumentationPage::class)))->makePriority());
        $this->assertSame(2, (new NavigationConfigTestClass($this->makeCoreDataObject('02_bar.md', pageClass: DocumentationPage::class)))->makePriority());
        $this->assertSame(3, (new NavigationConfigTestClass($this->makeCoreDataObject('03_baz.md', pageClass: DocumentationPage::class)))->makePriority());
    }

    public function testItExtractsPriorityFromNumericalFilenamePrefixRegardlessOfLeadingZeroes()
    {
        $this->assertSame(123, (new NavigationConfigTestClass($this->makeCoreDataObject('123-foo.md', pageClass: DocumentationPage::class)))->makePriority());
        $this->assertSame(123, (new NavigationConfigTestClass($this->makeCoreDataObject('0123-foo.md', pageClass: DocumentationPage::class)))->makePriority());
        $this->assertSame(123, (new NavigationConfigTestClass($this->makeCoreDataObject('00123-foo.md', pageClass: DocumentationPage::class)))->makePriority());
        $this->assertSame(123, (new NavigationConfigTestClass($this->makeCoreDataObject('000123-foo.md', pageClass: DocumentationPage::class)))->makePriority());
        $this->assertSame(123, (new NavigationConfigTestClass($this->makeCoreDataObject('0000123-foo.md', pageClass: DocumentationPage::class)))->makePriority());
    }

    public function testItExtractsPriorityFromNumericalFilenamePrefixForNestedIdentifiers()
    {
        $this->assertSame(1, (new NavigationConfigTestClass($this->makeCoreDataObject('foo/01-bar.md', pageClass: DocumentationPage::class)))->makePriority());
        $this->assertSame(2, (new NavigationConfigTestClass($this->makeCoreDataObject('foo/bar/02-baz.md', pageClass: DocumentationPage::class)))->makePriority());
        $this->assertSame(3, (new NavigationConfigTestClass($this->makeCoreDataObject('foo/01-bar/03-baz.md', pageClass: DocumentationPage::class)))->makePriority());
    }

    public function testItDoesNotExtractNonNumericalFilenamePrefixes()
    {
        $this->assertSame(999, (new NavigationConfigTestClass($this->makeCoreDataObject('foo-bar.md', pageClass: DocumentationPage::class)))->makePriority());
        $this->assertSame(999, (new NavigationConfigTestClass($this->makeCoreDataObject('abc-bar.md', pageClass: DocumentationPage::class)))->makePriority());
    }

    public function testFrontMatterValueOverridesFilenamePrefixPriority()
    {
        $coreDataObject = new CoreDataObject(new FrontMatter(['navigation.priority' => 10]), new Markdown(), markdownPage::class, '05-test', '', '', '');
        $factory = new NavigationConfigTestClass($coreDataObject);

        $this->assertSame(10, $factory->makePriority());
    }

    protected function makeCoreDataObject(string $identifier = '', string $routeKey = '', string $pageClass = MarkdownPage::class): CoreDataObject
    {
        return new CoreDataObject(new FrontMatter(), new Markdown(), $pageClass, $identifier, '', '', $routeKey);
    }
}

class NavigationConfigTestClass extends NavigationDataFactory
{
    public function __construct(CoreDataObject $pageData)
    {
        parent::__construct($pageData, '');
    }

    public function makePriority(): int
    {
        return parent::makePriority();
    }

    public function makeLabel(): ?string
    {
        return parent::makeLabel();
    }

    public function makeHidden(): bool
    {
        return parent::makeHidden();
    }

    public function makeGroup(): ?string
    {
        return parent::makeGroup();
    }
}
