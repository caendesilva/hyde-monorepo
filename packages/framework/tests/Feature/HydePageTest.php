<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Foundation\Facades\Pages;
use Hyde\Foundation\Facades\Routes;
use Hyde\Foundation\HydeCoreExtension;
use Hyde\Framework\Exceptions\FileNotFoundException;
use Hyde\Hyde;
use Hyde\Facades\Filesystem;
use Hyde\Markdown\Models\Markdown;
use Hyde\Pages\BladePage;
use Hyde\Pages\Concerns\BaseMarkdownPage;
use Hyde\Pages\Concerns\HydePage;
use Hyde\Pages\DocumentationPage;
use Hyde\Pages\HtmlPage;
use Hyde\Pages\MarkdownPage;
use Hyde\Pages\MarkdownPost;
use Hyde\Support\Models\Route;
use Hyde\Testing\TestCase;

/**
 * Test the base HydePage class.
 *
 * @covers \Hyde\Pages\Concerns\HydePage
 * @covers \Hyde\Pages\Concerns\BaseMarkdownPage
 * @covers \Hyde\Framework\Factories\Concerns\HasFactory
 * @covers \Hyde\Framework\Factories\NavigationDataFactory
 * @covers \Hyde\Framework\Factories\FeaturedImageFactory
 * @covers \Hyde\Framework\Factories\HydePageDataFactory
 * @covers \Hyde\Framework\Factories\BlogPostDataFactory
 * @covers \Hyde\Framework\Concerns\InteractsWithFrontMatter
 */
class HydePageTest extends TestCase
{
    // Section: Baseline tests (Abstract class)

    public function testBaseSourceDirectory()
    {
        $this->assertSame('', HydePage::sourceDirectory());
    }

    public function testBaseOutputDirectory()
    {
        $this->assertSame('', HydePage::outputDirectory());
    }

    public function testBaseFileExtension()
    {
        $this->assertSame('', HydePage::fileExtension());
    }

    public function testBaseSourcePath()
    {
        $this->assertSame('hello-world', HydePage::sourcePath('hello-world'));
    }

    public function testBaseOutputPath()
    {
        $this->assertSame('hello-world.html', HydePage::outputPath('hello-world'));
    }

    public function testBasePath()
    {
        $this->assertSame(Hyde::path('hello-world'), HydePage::path('hello-world'));
    }

    public function testBasePathToIdentifier()
    {
        $this->assertSame('hello-world', HydePage::pathToIdentifier('hello-world'));
    }

    public function testBaseBaseRouteKey()
    {
        $this->assertSame(HydePage::outputDirectory(), HydePage::baseRouteKey());
    }

    public function testBaseIsDiscoverable()
    {
        $this->assertFalse(HydePage::isDiscoverable());
    }

    // Section: Baseline tests (Basic child class)

    public function testSourceDirectory()
    {
        $this->assertSame('source', TestPage::sourceDirectory());
    }

    public function testOutputDirectory()
    {
        $this->assertSame('output', TestPage::outputDirectory());
    }

    public function testFileExtension()
    {
        $this->assertSame('.md', TestPage::fileExtension());
    }

    public function testSourcePath()
    {
        $this->assertSame('source/hello-world.md', TestPage::sourcePath('hello-world'));
    }

    public function testOutputPath()
    {
        $this->assertSame('output/hello-world.html', TestPage::outputPath('hello-world'));
    }

    public function testPath()
    {
        $this->assertSame(Hyde::path('source/hello-world'), TestPage::path('hello-world'));
    }

    public function testBaseRouteKey()
    {
        $this->assertSame(TestPage::outputDirectory(), TestPage::baseRouteKey());
    }

    public function testIsDiscoverable()
    {
        $this->assertTrue(TestPage::isDiscoverable());
    }

    public function testGetSourcePath()
    {
        $this->assertSame(
            'source/hello-world.md',
            (new TestPage('hello-world'))->getSourcePath()
        );
    }

    public function testGetOutputPath()
    {
        $this->assertSame(
            'output/hello-world.html',
            (new TestPage('hello-world'))->getOutputPath()
        );
    }

    public function testGetLink()
    {
        $this->assertSame(
            'output/hello-world.html',
            (new TestPage('hello-world'))->getLink()
        );
    }

    public function testMake()
    {
        $this->assertEquals(TestPage::make(), new TestPage());
    }

    public function testMakeWithData()
    {
        $this->assertEquals(
            TestPage::make('foo', ['foo' => 'bar']),
            new TestPage('foo', ['foo' => 'bar'])
        );
    }

    public function testShowInNavigation()
    {
        $this->assertTrue((new BladePage('foo'))->showInNavigation());
        $this->assertTrue((new MarkdownPage())->showInNavigation());
        $this->assertTrue((new DocumentationPage())->showInNavigation());
        $this->assertFalse((new MarkdownPost())->showInNavigation());
        $this->assertTrue((new HtmlPage())->showInNavigation());
    }

    public function testNavigationMenuPriority()
    {
        $this->assertSame(999, (new BladePage('foo'))->navigationMenuPriority());
        $this->assertSame(999, (new MarkdownPage())->navigationMenuPriority());
        $this->assertSame(999, (new DocumentationPage())->navigationMenuPriority());
        $this->assertSame(10, (new MarkdownPost())->navigationMenuPriority());
        $this->assertSame(999, (new HtmlPage())->navigationMenuPriority());
    }

    public function testNavigationMenuLabel()
    {
        $this->assertSame('Foo', (new BladePage('foo'))->navigationMenuLabel());
        $this->assertSame('Foo', (new MarkdownPage('foo'))->navigationMenuLabel());
        $this->assertSame('Foo', (new MarkdownPost('foo'))->navigationMenuLabel());
        $this->assertSame('Foo', (new DocumentationPage('foo'))->navigationMenuLabel());
        $this->assertSame('Foo', (new HtmlPage('foo'))->navigationMenuLabel());
    }

    public function testNavigationMenuGroup()
    {
        $this->assertNull((new BladePage('foo'))->navigationMenuGroup());
        $this->assertNull((new MarkdownPage())->navigationMenuGroup());
        $this->assertNull((new MarkdownPost())->navigationMenuGroup());
        $this->assertNull((new HtmlPage())->navigationMenuGroup());
        $this->assertNull((new DocumentationPage())->navigationMenuGroup());
        $this->assertSame('foo', DocumentationPage::make(matter: ['navigation' => ['group' => 'foo']])->navigationMenuGroup());
    }

    public function testToArray()
    {
        $this->assertSame([
            'class',
            'identifier',
            'routeKey',
            'matter',
            'metadata',
            'navigation',
            'title',
        ],
            array_keys((new TestPage('hello-world'))->toArray())
        );
    }

    // Section: In-depth tests

    public function testGetSourceDirectoryReturnsStaticProperty()
    {
        MarkdownPage::setSourceDirectory('foo');

        $this->assertSame('foo', MarkdownPage::sourceDirectory());
        $this->resetDirectoryConfiguration();
    }

    public function testSetSourceDirectoryTrimsTrailingSlashes()
    {
        MarkdownPage::setSourceDirectory('/foo/\\');

        $this->assertSame('foo', MarkdownPage::sourceDirectory());
        $this->resetDirectoryConfiguration();
    }

    public function testGetOutputDirectoryReturnsStaticProperty()
    {
        MarkdownPage::setOutputDirectory('foo');

        $this->assertSame('foo', MarkdownPage::outputDirectory());
        $this->resetDirectoryConfiguration();
    }

    public function testSetOutputDirectoryTrimsTrailingSlashes()
    {
        MarkdownPage::setOutputDirectory('/foo/\\');

        $this->assertSame('foo', MarkdownPage::outputDirectory());
        $this->resetDirectoryConfiguration();
    }

    public function testGetFileExtensionReturnsStaticProperty()
    {
        MarkdownPage::setFileExtension('.foo');

        $this->assertSame('.foo', MarkdownPage::fileExtension());
        $this->resetDirectoryConfiguration();
    }

    public function testSetFileExtensionForcesLeadingPeriod()
    {
        MarkdownPage::setFileExtension('foo');

        $this->assertSame('.foo', MarkdownPage::fileExtension());
        $this->resetDirectoryConfiguration();
    }

    public function testSetFileExtensionRemovesTrailingPeriod()
    {
        MarkdownPage::setFileExtension('foo.');

        $this->assertSame('.foo', MarkdownPage::fileExtension());
        $this->resetDirectoryConfiguration();
    }

    public function testGetIdentifierReturnsIdentifierProperty()
    {
        $page = new MarkdownPage('foo');
        $this->assertSame('foo', $page->getIdentifier());
    }

    public function testSetSourceDirectory()
    {
        ConfigurableSourcesTestPage::setSourceDirectory('foo');
        $this->assertSame('foo', ConfigurableSourcesTestPage::sourceDirectory());
    }

    public function testSetOutputDirectory()
    {
        ConfigurableSourcesTestPage::setOutputDirectory('foo');
        $this->assertSame('foo', ConfigurableSourcesTestPage::outputDirectory());
    }

    public function testSetFileExtension()
    {
        ConfigurableSourcesTestPage::setFileExtension('.foo');
        $this->assertSame('.foo', ConfigurableSourcesTestPage::fileExtension());
    }

    public function testStaticGetMethodReturnsDiscoveredPage()
    {
        $this->assertEquals(BladePage::parse('index'), BladePage::get('index'));
    }

    public function testStaticGetMethodThrowsExceptionIfPageNotFound()
    {
        $this->expectException(FileNotFoundException::class);
        BladePage::get('foo');
    }

    public function testParseParsesSuppliedSlugIntoAPageModel()
    {
        Filesystem::touch('_pages/foo.md');

        $this->assertInstanceOf(MarkdownPage::class, $page = MarkdownPage::parse('foo'));
        $this->assertSame('foo', $page->identifier);

        Filesystem::unlink('_pages/foo.md');
    }

    public function testFilesReturnsArrayOfSourceFiles()
    {
        Filesystem::touch('_pages/foo.md');

        $this->assertSame(['foo'], MarkdownPage::files());

        Filesystem::unlink('_pages/foo.md');
    }

    public function testAllReturnsCollectionOfAllParsedSourceFilesFromPageIndex()
    {
        Filesystem::touch('_pages/foo.md');

        $this->assertEquals(
            Pages::getPages(MarkdownPage::class),
            MarkdownPage::all()
        );

        $this->assertEquals(
            ['_pages/foo.md' => (new MarkdownPage('foo'))],
            MarkdownPage::all()->all()
        );

        Filesystem::unlink('_pages/foo.md');
    }

    public function testQualifyBasenameProperlyExpandsBasenameForTheModel()
    {
        $this->assertSame('_pages/foo.md', MarkdownPage::sourcePath('foo'));
    }

    public function testQualifyBasenameTrimsSlashesFromInput()
    {
        $this->assertSame('_pages/foo.md', MarkdownPage::sourcePath('/foo/\\'));
    }

    public function testQualifyBasenameUsesTheStaticProperties()
    {
        MarkdownPage::setSourceDirectory('foo');
        MarkdownPage::setFileExtension('txt');

        $this->assertSame('foo/bar.txt', MarkdownPage::sourcePath('bar'));

        $this->resetDirectoryConfiguration();
    }

    public function testPathReturnsAbsolutePathToSourceDirectoryWhenNoParameterIsSupplied()
    {
        $this->assertSame(Hyde::path('source'), TestPage::path());
    }

    public function testPathReturnsAbsolutePathToFileInSourceDirectoryWhenParameterIsSupplied()
    {
        $this->assertSame(Hyde::path('source/foo.md'), TestPage::path('foo.md'));
    }

    public function testPathMethodRemovesTrailingSlashes()
    {
        $this->assertSame(Hyde::path('source/foo.md'), TestPage::path('/foo.md/'));
    }

    public function testGetOutputLocationReturnsTheFileOutputPathForTheSuppliedBasename()
    {
        $this->assertSame('foo.html', MarkdownPage::outputPath('foo'));
    }

    public function testGetOutputLocationReturnsTheConfiguredLocation()
    {
        MarkdownPage::setOutputDirectory('foo');

        $this->assertSame('foo/bar.html', MarkdownPage::outputPath('bar'));

        $this->resetDirectoryConfiguration();
    }

    public function testGetOutputLocationTrimsTrailingSlashesFromDirectorySetting()
    {
        MarkdownPage::setOutputDirectory('/foo/\\');

        $this->assertSame('foo/bar.html', MarkdownPage::outputPath('bar'));

        $this->resetDirectoryConfiguration();
    }

    public function testGetOutputLocationTrimsTrailingSlashesFromBasename()
    {
        $this->assertSame('foo.html', MarkdownPage::outputPath('/foo/\\'));
    }

    public function testGetRouteKeyReturnsOutputDirectoryAndBasename()
    {
        $page = new MarkdownPage('foo');
        $this->assertSame('foo', $page->getRouteKey());
    }

    public function testGetRouteKeyReturnsOutputDirectoryAndBasenameForConfiguredDirectory()
    {
        MarkdownPage::setOutputDirectory('foo');

        $page = new MarkdownPage('bar');
        $this->assertSame('foo/bar', $page->getRouteKey());

        $this->resetDirectoryConfiguration();
    }

    public function testGetRouteKeyTrimsTrailingSlashesFromDirectorySetting()
    {
        MarkdownPage::setOutputDirectory('/foo/\\');

        $page = new MarkdownPage('bar');
        $this->assertSame('foo/bar', $page->getRouteKey());

        $this->resetDirectoryConfiguration();
    }

    public function testGetOutputPathReturnsCurrentPagePathWithHtmlExtensionAppended()
    {
        $page = new MarkdownPage('foo');
        $this->assertSame('foo.html', $page->getOutputPath());
    }

    public function testGetSourcePathReturnsQualifiedBasename()
    {
        $this->assertSame(
            MarkdownPage::sourcePath('foo'),
            (new MarkdownPage('foo'))->getSourcePath()
        );
    }

    public function testMarkdownPageImplementsPageContract()
    {
        $this->assertInstanceOf(HydePage::class, new MarkdownPage());
    }

    public function testAllPageModelsExtendAbstractPage()
    {
        $pages = [
            HtmlPage::class,
            BladePage::class,
            MarkdownPage::class,
            MarkdownPost::class,
            DocumentationPage::class,
        ];

        foreach ($pages as $page) {
            $this->assertInstanceOf(HydePage::class, new $page());
        }

        $this->assertInstanceOf(HydePage::class, new BladePage('foo'));
    }

    public function testAllPageModelsHaveConfiguredSourceDirectory()
    {
        $pages = [
            HtmlPage::class => '_pages',
            BladePage::class => '_pages',
            MarkdownPage::class => '_pages',
            MarkdownPost::class => '_posts',
            DocumentationPage::class => '_docs',
        ];

        foreach ($pages as $page => $expected) {
            assert(is_a($page, HydePage::class, true));
            $this->assertSame($expected, $page::sourceDirectory());
        }
    }

    public function testAllPageModelsHaveConfiguredOutputDirectory()
    {
        $pages = [
            BladePage::class => '',
            MarkdownPage::class => '',
            MarkdownPost::class => 'posts',
            DocumentationPage::class => 'docs',
        ];

        foreach ($pages as $page => $expected) {
            assert(is_a($page, HydePage::class, true));
            $this->assertSame($expected, $page::outputDirectory());
        }
    }

    public function testAllPageModelsHaveConfiguredFileExtension()
    {
        $pages = [
            BladePage::class => '.blade.php',
            MarkdownPage::class => '.md',
            MarkdownPost::class => '.md',
            DocumentationPage::class => '.md',
        ];

        foreach ($pages as $page => $expected) {
            assert(is_a($page, HydePage::class, true));
            $this->assertSame($expected, $page::fileExtension());
        }
    }

    public function testAbstractMarkdownPageExtendsAbstractPage()
    {
        $this->assertInstanceOf(HydePage::class, $this->mock(BaseMarkdownPage::class));
    }

    public function testAbstractMarkdownPageImplementsPageContract()
    {
        $this->assertInstanceOf(HydePage::class, $this->mock(BaseMarkdownPage::class));
    }

    public function testAbstractMarkdownPageHasMarkdownDocumentProperty()
    {
        $this->assertTrue(property_exists(BaseMarkdownPage::class, 'markdown'));
    }

    public function testAbstractMarkdownPageHasFileExtensionProperty()
    {
        $this->assertTrue(property_exists(BaseMarkdownPage::class, 'fileExtension'));
    }

    public function testAbstractMarkdownPageFileExtensionPropertyIsSetToMd()
    {
        $this->assertSame('.md', BaseMarkdownPage::fileExtension());
    }

    public function testAbstractMarkdownPageConstructorArgumentsAreOptional()
    {
        $page = $this->mock(BaseMarkdownPage::class);

        $this->assertInstanceOf(BaseMarkdownPage::class, $page);
    }

    public function testAbstractMarkdownPageConstructorAssignsMarkdownDocumentPropertyIfSet()
    {
        $markdown = new Markdown();
        $page = new MarkdownPage(markdown: $markdown);

        $this->assertSame($markdown, $page->markdown);
    }

    public function testAbstractMarkdownPageConstructorCreatesNewMarkdownDocumentIfNoMarkdownDocumentIsSet()
    {
        $page = new MarkdownPage();
        $this->assertInstanceOf(Markdown::class, $page->markdown);
    }

    public function testAbstractMarkdownPageMarkdownHelperReturnsTheMarkdownDocumentInstance()
    {
        $page = new MarkdownPage();
        $this->assertSame($page->markdown, $page->markdown());
    }

    public function testAbstractMarkdownPageMarkdownHelperReturnsTheConfiguredMarkdownDocumentInstance()
    {
        $markdown = new Markdown();
        $page = new MarkdownPage(markdown: $markdown);

        $this->assertSame($markdown, $page->markdown());
    }

    public function testAbstractMarkdownPageMakeHelperConstructsDynamicTitleAutomatically()
    {
        $page = MarkdownPage::make('', ['title' => 'Foo']);
        $this->assertSame('Foo', $page->title);
    }

    public function testMarkdownBasedPagesExtendAbstractMarkdownPage()
    {
        $pages = [
            MarkdownPage::class,
            MarkdownPost::class,
            DocumentationPage::class,
        ];

        foreach ($pages as $page) {
            $this->assertInstanceOf(BaseMarkdownPage::class, new $page());
        }
    }

    public function testBladePagesDoNotExtendAbstractMarkdownPage()
    {
        $this->assertNotInstanceOf(BaseMarkdownPage::class, new BladePage('foo'));
    }

    public function testGetRouteReturnsPageRoute()
    {
        $page = new MarkdownPage();
        $this->assertEquals(new Route($page), $page->getRoute());
    }

    public function testGetRouteReturnsTheRouteObjectFromTheRouterIndex()
    {
        $this->file('_pages/foo.md');

        $page = MarkdownPage::parse('foo');
        $this->assertSame(Routes::get('foo'), $page->getRoute());
    }

    public function testHtmlTitleReturnsSiteNamePlusPageTitle()
    {
        $make = MarkdownPage::make('', ['title' => 'Foo']);
        $this->assertSame('HydePHP - Foo', $make->title());
    }

    public function testHtmlTitleUsesConfiguredSiteName()
    {
        config(['hyde.name' => 'Foo Bar']);

        $markdownPage = new MarkdownPage('Foo');
        $this->assertSame('Foo Bar - Foo', $markdownPage->title());
    }

    public function testBodyHelperReturnsMarkdownDocumentBodyInMarkdownPages()
    {
        $page = new MarkdownPage(markdown: new Markdown(body: '# Foo'));
        $this->assertSame('# Foo', $page->markdown->body());
    }

    public function testShowInNavigationReturnsFalseForMarkdownPost()
    {
        $page = MarkdownPost::make();

        $this->assertFalse($page->showInNavigation());
    }

    public function testShowInNavigationReturnsTrueForDocumentationPageIfSlugIsIndex()
    {
        $page = DocumentationPage::make('index');

        $this->assertTrue($page->showInNavigation());
    }

    public function testShowInNavigationReturnsTrueForDocumentationPageIfSlugIsNotIndex()
    {
        $page = DocumentationPage::make('not-index');

        $this->assertTrue($page->showInNavigation());
    }

    public function testShowInNavigationReturnsFalseForAbstractMarkdownPageIfMatterNavigationHiddenIsTrue()
    {
        $page = MarkdownPage::make('foo', ['navigation.hidden' => true]);

        $this->assertFalse($page->showInNavigation());
    }

    public function testShowInNavigationReturnsTrueForAbstractMarkdownPageIfMatterNavigationVisibleIsTrue()
    {
        $page = MarkdownPage::make('foo', ['navigation.visible' => true]);

        $this->assertTrue($page->showInNavigation());
    }

    public function testShowInNavigationReturnsTrueForAbstractMarkdownPageIfMatterNavigationHiddenIsFalse()
    {
        $page = MarkdownPage::make('foo', ['navigation.hidden' => false]);

        $this->assertTrue($page->showInNavigation());
    }

    public function testShowInNavigationReturnsTrueForAbstractMarkdownPageIfMatterNavigationHiddenIsNotSet()
    {
        $page = MarkdownPage::make('foo', ['navigation.hidden' => null]);

        $this->assertTrue($page->showInNavigation());
    }

    public function testShowInNavigationReturnsFalseIfSlugIsPresentInConfigHydeNavigationExclude()
    {
        $page = MarkdownPage::make('foo');
        $this->assertTrue($page->showInNavigation());

        config(['hyde.navigation.exclude' => ['foo']]);

        $page = MarkdownPage::make('foo');
        $this->assertFalse($page->showInNavigation());
    }

    public function testShowInNavigationReturnsFalseIfSlugIs404()
    {
        $page = MarkdownPage::make('404');
        $this->assertFalse($page->showInNavigation());
    }

    public function testShowInNavigationDefaultsToTrueIfAllChecksPass()
    {
        $page = MarkdownPage::make('foo');
        $this->assertTrue($page->showInNavigation());
    }

    public function testNavigationMenuPriorityReturnsFrontMatterValueOfNavigationPriorityIfAbstractMarkdownPageAndNotNull()
    {
        $page = MarkdownPage::make('foo', ['navigation.priority' => 1]);
        $this->assertSame(1, $page->navigationMenuPriority());
    }

    public function testNavigationMenuPriorityCanBeSetUsingOrderProperty()
    {
        $page = MarkdownPage::make('foo', ['navigation.order' => 1]);
        $this->assertSame(1, $page->navigationMenuPriority());
    }

    public function testNavigationMenuPriorityReturnsSpecifiedConfigValueIfSlugExistsInConfigHydeNavigationOrder()
    {
        $page = MarkdownPage::make('foo');
        $this->assertSame(999, $page->navigationMenuPriority());

        config(['hyde.navigation.order' => ['foo' => 1]]);

        $page = MarkdownPage::make('foo');
        $this->assertSame(1, $page->navigationMenuPriority());
    }

    public function testNavigationMenuPriorityGivesPrecedenceToFrontMatterOverConfigHydeNavigationOrder()
    {
        $page = MarkdownPage::make('foo', ['navigation.priority' => 1]);

        $this->assertSame(1, $page->navigationMenuPriority());

        config(['hyde.navigation.order' => ['foo' => 2]]);

        $page = MarkdownPage::make('foo', ['navigation.priority' => 1]);
        $this->assertSame(1, $page->navigationMenuPriority());
    }

    public function testNavigationMenuPriorityReturns999ForDocumentationPage()
    {
        $page = DocumentationPage::make('index');
        $this->assertSame(999, $page->navigationMenuPriority());
    }

    public function testNavigationMenuPriorityReturns0IfSlugIsIndex()
    {
        $page = MarkdownPage::make('index');
        $this->assertSame(0, $page->navigationMenuPriority());
    }

    public function testNavigationMenuPriorityReturns10IfSlugIsPosts()
    {
        $page = MarkdownPage::make('posts');
        $this->assertSame(10, $page->navigationMenuPriority());
    }

    public function testNavigationMenuPriorityDefaultsTo999IfNoOtherConditionsAreMet()
    {
        $page = MarkdownPage::make('foo');
        $this->assertSame(999, $page->navigationMenuPriority());
    }

    public function testNavigationMenuTitleReturnsNavigationTitleMatterIfSet()
    {
        $page = MarkdownPage::make('foo', ['navigation.label' => 'foo']);
        $this->assertSame('foo', $page->navigationMenuLabel());
    }

    public function testNavigationMenuTitleReturnsTitleMatterIfSet()
    {
        $page = MarkdownPage::make('foo', ['title' => 'foo']);
        $this->assertSame('foo', $page->navigationMenuLabel());
    }

    public function testNavigationMenuTitleNavigationTitleHasPrecedenceOverTitle()
    {
        $page = MarkdownPage::make('foo', ['title' => 'foo', 'navigation.label' => 'bar']);
        $this->assertSame('bar', $page->navigationMenuLabel());
    }

    public function testNavigationMenuTitleReturnsDocsIfSlugIsIndexAndModelIsDocumentationPage()
    {
        $page = DocumentationPage::make('index');
        $this->assertSame('Docs', $page->navigationMenuLabel());
    }

    public function testNavigationMenuTitleReturnsHomeIfSlugIsIndexAndModelIsNotDocumentationPage()
    {
        $page = MarkdownPage::make('index');
        $this->assertSame('Home', $page->navigationMenuLabel());
    }

    public function testNavigationMenuTitleReturnsTitleIfTitleIsSetAndNotEmpty()
    {
        $page = MarkdownPage::make('bar', ['title' => 'foo']);
        $this->assertSame('foo', $page->navigationMenuLabel());
    }

    public function testNavigationMenuTitleFallsBackToHydeMakeTitleFromSlug()
    {
        $page = MarkdownPage::make('foo');
        $this->assertSame('Foo', $page->navigationMenuLabel());
    }

    public function testNavigationMenuTitleCanBeSetInConfiguration()
    {
        config(['hyde.navigation.labels' => ['foo' => 'bar']]);

        $page = MarkdownPage::make('foo');
        $this->assertSame('bar', $page->navigationMenuLabel());
    }

    public function testDocumentationPageCanBeHiddenFromNavigationUsingConfig()
    {
        config(['hyde.navigation.exclude' => ['docs/index']]);

        $page = DocumentationPage::make('index');
        $this->assertFalse($page->showInNavigation());
    }

    public function testGetCanonicalUrlReturnsUrlForTopLevelPage()
    {
        $this->withSiteUrl();

        $page = new MarkdownPage('foo');
        $this->assertSame('https://example.com/foo.html', $page->getCanonicalUrl());
    }

    public function testGetCanonicalUrlReturnsPrettyUrlForTopLevelPage()
    {
        $this->withSiteUrl();
        config(['hyde.pretty_urls' => true]);

        $page = new MarkdownPage('foo');

        $this->assertSame('https://example.com/foo', $page->getCanonicalUrl());
    }

    public function testGetCanonicalUrlReturnsUrlForNestedPage()
    {
        $this->withSiteUrl();

        $page = new MarkdownPage('foo/bar');

        $this->assertSame('https://example.com/foo/bar.html', $page->getCanonicalUrl());
    }

    public function testGetCanonicalUrlReturnsUrlForDeeplyNestedPage()
    {
        $this->withSiteUrl();

        $page = new MarkdownPage('foo/bar/baz');

        $this->assertSame('https://example.com/foo/bar/baz.html', $page->getCanonicalUrl());
    }

    public function testCanonicalUrlIsNotSetWhenIdentifierIsNull()
    {
        $this->withSiteUrl();

        $page = new MarkdownPage();

        $this->assertNull($page->getCanonicalUrl());
        $this->assertStringNotContainsString('canonical', $page->metadata()->render());
        $this->assertStringNotContainsString('<link rel="canonical"', $page->metadata()->render());
    }

    public function testCanonicalUrlIsNotSetWhenSiteUrlIsNull()
    {
        $this->withoutSiteUrl();

        $page = new MarkdownPage('foo');

        $this->assertNull($page->getCanonicalUrl());
        $this->assertStringNotContainsString('canonical', $page->metadata()->render());
        $this->assertStringNotContainsString('<link rel="canonical"', $page->metadata()->render());
    }

    public function testCustomCanonicalLinkCanBeSetInFrontMatter()
    {
        $this->withSiteUrl();

        $page = MarkdownPage::make(matter: ['canonicalUrl' => 'foo/bar']);

        $this->assertSame('foo/bar', $page->getCanonicalUrl());
        $this->assertStringContainsString('<link rel="canonical" href="foo/bar">', $page->metadata()->render());
    }

    public function testCanCreateCanonicalUrlUsingBaseUrlFromConfig()
    {
        config(['hyde' => [
            'url' => 'https://example.com',
        ]]);

        $this->assertSame('https://example.com/foo.html', (new MarkdownPage('foo'))->getCanonicalUrl());
    }

    public function testCanCreateCanonicalUrlUsingBaseUrlFromConfigUsingPrettyUrls()
    {
        config(['hyde' => [
            'url' => 'https://example.com',
            'pretty_urls' => true,
        ]]);

        $this->assertSame('https://example.com/foo', (new MarkdownPage('foo'))->getCanonicalUrl());
    }

    public function testCanonicalUrlIsNullWhenNoBaseUrlIsSet()
    {
        config(['hyde' => []]);

        $this->assertNull((new MarkdownPage('foo'))->getCanonicalUrl());
    }

    public function testRenderPageMetadataReturnsString()
    {
        $page = new MarkdownPage('foo');
        $this->assertIsString($page->metadata()->render());
    }

    public function testHasMethodReturnsTrueIfPageHasStandardProperty()
    {
        $page = new MarkdownPage('foo');
        $this->assertTrue($page->has('identifier'));
    }

    public function testHasMethodReturnsFalseIfPageDoesNotHaveStandardProperty()
    {
        $page = new MarkdownPage();
        $this->assertFalse($page->has('foo'));
    }

    public function testHasMethodReturnsTrueIfPageHasDynamicProperty()
    {
        $page = new MarkdownPage();
        $page->foo = 'bar';
        $this->assertTrue($page->has('foo'));
    }

    public function testHasMethodReturnsFalseIfPageDoesNotHaveDynamicProperty()
    {
        $page = new MarkdownPage();
        $this->assertFalse($page->has('foo'));
    }

    public function testHasMethodReturnsTrueIfPageHasPropertySetInFrontMatter()
    {
        $page = MarkdownPage::make(matter: ['foo' => 'bar']);
        $this->assertTrue($page->has('foo'));
    }

    public function testHasMethodReturnsFalseIfPageDoesNotHavePropertySetInFrontMatter()
    {
        $page = MarkdownPage::make();
        $this->assertFalse($page->has('foo'));
    }

    public function testHasMethodReturnsFalseIfPropertyExistsButIsBlank()
    {
        $page = MarkdownPage::make();
        $page->foo = null;
        $this->assertFalse($page->has('foo'));

        $page = MarkdownPage::make();
        $page->foo = '';
        $this->assertFalse($page->has('foo'));
    }

    public function testHasMethodReturnsTrueIfPageHasBlankPropertySetInFrontMatter()
    {
        $this->assertFalse(MarkdownPage::make(matter: ['foo' => null])->has('foo'));
        $this->assertFalse(MarkdownPage::make(matter: ['foo' => ''])->has('foo'));
    }

    public function testMarkdownPagesCanBeSavedToDisk()
    {
        $page = new MarkdownPage('foo');
        $page->save();

        $this->assertFileExists(Hyde::path('_pages/foo.md'));

        Filesystem::unlink('_pages/foo.md');
    }

    public function testSaveMethodConvertsFrontMatterArrayToYamlBlock()
    {
        MarkdownPage::make('foo', matter: ['foo' => 'bar'])->save();

        $this->assertSame("---\nfoo: bar\n---\n",
            file_get_contents(Hyde::path('_pages/foo.md'))
        );

        Filesystem::unlink('_pages/foo.md');
    }

    public function testSaveMethodWritesPageBodyToFile()
    {
        MarkdownPage::make('foo', markdown: 'foo')->save();

        $this->assertSame("foo\n",
            file_get_contents(Hyde::path('_pages/foo.md'))
        );

        Filesystem::unlink('_pages/foo.md');
    }

    public function testSaveMethodWritesPageBodyToFileWithFrontMatter()
    {
        MarkdownPage::make('foo', matter: ['foo' => 'bar'], markdown: 'foo bar')->save();

        $this->assertSame("---\nfoo: bar\n---\n\nfoo bar\n",
            file_get_contents(Hyde::path('_pages/foo.md'))
        );

        Filesystem::unlink('_pages/foo.md');
    }

    public function testNewMarkdownPagesCanBeSaved()
    {
        $page = new MarkdownPage('foo');
        $page->save();

        $this->assertFileExists(Hyde::path('_pages/foo.md'));
        $this->assertSame('', file_get_contents(Hyde::path('_pages/foo.md')));

        Filesystem::unlink('_pages/foo.md');
    }

    public function testExistingParsedMarkdownPagesCanBeSaved()
    {
        $page = new MarkdownPage('foo', markdown: 'bar');
        $page->save();

        $this->assertSame("bar\n", file_get_contents(Hyde::path('_pages/foo.md')));

        $parsed = Pages::getPage('_pages/foo.md');
        $this->assertInstanceOf(MarkdownPage::class, $parsed);
        $this->assertSame('bar', $parsed->markdown->body());

        $parsed->markdown = new Markdown('baz');
        $parsed->save();

        $this->assertSame("baz\n", file_get_contents(Hyde::path('_pages/foo.md')));

        Filesystem::unlink('_pages/foo.md');
    }

    public function testSaveMethodCreatesSourceDirectoryIfItDoesNotExist()
    {
        $this->assertDirectoryDoesNotExist(Hyde::path('foo'));

        $page = new MissingSourceDirectoryMarkdownPage('bar');
        $page->save();

        $this->assertDirectoryExists(Hyde::path('foo'));
        $this->assertFileExists(Hyde::path('foo/bar.md'));

        Filesystem::deleteDirectory('foo');
    }

    public function testSaveMethodCreatesSourceDirectoryRecursivelyIfItDoesNotExist()
    {
        $this->assertDirectoryDoesNotExist(Hyde::path('foo'));

        $page = new MissingSourceDirectoryMarkdownPage('bar/baz');
        $page->save();

        $this->assertDirectoryExists(Hyde::path('foo'));
        $this->assertDirectoryExists(Hyde::path('foo/bar'));
        $this->assertFileExists(Hyde::path('foo/bar/baz.md'));

        Filesystem::deleteDirectory('foo');
    }

    public function testMarkdownPostsCanBeSaved()
    {
        $post = new MarkdownPost('foo');
        $post->save();

        $this->assertFileExists(Hyde::path('_posts/foo.md'));

        Filesystem::unlink('_posts/foo.md');
    }

    public function testDocumentationPagesCanBeSaved()
    {
        $page = new DocumentationPage('foo');
        $page->save();

        $this->assertFileExists(Hyde::path('_docs/foo.md'));

        Filesystem::unlink('_docs/foo.md');
    }

    public function testGetMethodCanAccessDataFromPage()
    {
        $page = MarkdownPage::make('foo', ['foo' => 'bar']);
        $this->assertSame('bar', $page->data('foo'));
    }

    public function testGetMethodCanAccessNestedDataFromPage()
    {
        $page = MarkdownPage::make('foo', ['foo' => ['bar' => 'baz']]);
        $this->assertSame('baz', $page->data('foo')['bar']);
    }

    public function testGetMethodCanAccessNestedDataFromPageWithDotNotation()
    {
        $page = MarkdownPage::make('foo', ['foo' => ['bar' => 'baz']]);
        $this->assertSame('baz', $page->data('foo.bar'));
    }

    public function testGetLinkWithPrettyUrls()
    {
        config(['hyde.pretty_urls' => true]);

        $this->assertSame('output/hello-world', (new TestPage('hello-world'))->getLink());
    }

    public function testGetLinkUsesHyperlinksHelper()
    {
        $this->assertSame(
            Hyde::formatLink((new TestPage('hello-world'))->getOutputPath()),
            (new TestPage('hello-world'))->getLink()
        );
    }

    public function testPathHelpersReturnSameResultAsFluentFilesystemHelpers()
    {
        $this->assertSameIgnoringDirSeparatorType(BladePage::path('foo'), BladePage::path('foo'));
        $this->assertSameIgnoringDirSeparatorType(MarkdownPage::path('foo'), MarkdownPage::path('foo'));
        $this->assertSameIgnoringDirSeparatorType(MarkdownPost::path('foo'), MarkdownPost::path('foo'));
        $this->assertSameIgnoringDirSeparatorType(DocumentationPage::path('foo'), DocumentationPage::path('foo'));
    }

    public function testAllPagesAreRoutable()
    {
        $pages = [
            BladePage::class,
            MarkdownPage::class,
            MarkdownPost::class,
            DocumentationPage::class,
            HtmlPage::class,
        ];

        foreach ($pages as $page) {
            assert(is_a($page, HydePage::class, true));

            Hyde::boot();

            $page = new $page('foo');

            $this->assertInstanceOf(Route::class, $page->getRoute());
            $this->assertEquals(new Route($page), $page->getRoute());
            $this->assertSame($page->getRoute()->getLink(), $page->getLink());

            Filesystem::touch($page::sourcePath('foo'));
            Hyde::boot();

            $this->assertArrayHasKey($page->getSourcePath(), Hyde::pages());
            $this->assertArrayHasKey($page->getRouteKey(), Hyde::routes());

            unlink($page::sourcePath('foo'));
        }
    }

    public function testNavigationDataFactoryHidesPageFromNavigationWhenInASubdirectory()
    {
        $page = MarkdownPage::make('foo/bar');

        $this->assertFalse($page->showInNavigation());
        $this->assertNull($page->navigationMenuGroup());
    }

    public function testNavigationDataFactoryHidesPageFromNavigationWhenInAAndConfigIsSetToHidden()
    {
        config(['hyde.navigation.subdirectory_display' => 'hidden']);

        $page = MarkdownPage::make('foo/bar');

        $this->assertFalse($page->showInNavigation());
        $this->assertNull($page->navigationMenuGroup());
    }

    public function testNavigationDataFactoryDoesNotHidePageFromNavigationWhenInASubdirectoryAndAllowedInConfiguration()
    {
        config(['hyde.navigation.subdirectory_display' => 'flat']);

        $page = MarkdownPage::make('foo/bar');

        $this->assertTrue($page->showInNavigation());
        $this->assertNull($page->navigationMenuGroup());
    }

    public function testNavigationDataFactoryAllowsShowInNavigationAndSetsGroupWhenDropdownIsSelectedInConfig()
    {
        config(['hyde.navigation.subdirectory_display' => 'dropdown']);

        $page = MarkdownPage::make('foo/bar');

        $this->assertTrue($page->showInNavigation());
        $this->assertSame('foo', $page->navigationMenuGroup());
    }

    public function testIsDiscoverableMethodReturnsTrueForDiscoverablePages()
    {
        $this->assertTrue(DiscoverableTestPage::isDiscoverable());
    }

    public function testIsDiscoverableMethodReturnsFalseForNonDiscoverablePages()
    {
        $this->assertFalse(NonDiscoverableTestPage::isDiscoverable());
    }

    public function testIsDiscoverableMethodRequiresAllRequiredDataToBePresent()
    {
        $this->assertFalse(PartiallyDiscoverablePage::isDiscoverable());
    }

    public function testIsDiscoverableMethodRequiresSourceDirectoryToBeFilled()
    {
        $this->assertFalse(DiscoverablePageWithInvalidSourceDirectory::isDiscoverable());
    }

    public function testAllCoreExtensionPagesAreDiscoverable()
    {
        foreach (HydeCoreExtension::getPageClasses() as $page) {
            assert(is_a($page, HydePage::class, true));
            $this->assertTrue($page::isDiscoverable());
        }
    }

    public function testNestedIndexPagesShowUpInNavigation()
    {
        $page = MarkdownPage::make('foo/index');

        $this->assertTrue($page->showInNavigation());
        $this->assertSame('Foo', $page->navigationMenuLabel());
    }

    protected function assertSameIgnoringDirSeparatorType(string $expected, string $actual): void
    {
        $this->assertSame(
            str_replace('\\', '/', $expected),
            str_replace('\\', '/', $actual)
        );
    }

    protected function resetDirectoryConfiguration(): void
    {
        BladePage::setSourceDirectory('_pages');
        MarkdownPage::setSourceDirectory('_pages');
        MarkdownPost::setSourceDirectory('_posts');
        DocumentationPage::setSourceDirectory('_docs');
        MarkdownPage::setFileExtension('.md');
    }
}

class TestPage extends HydePage
{
    use VoidCompiler;

    public static string $sourceDirectory = 'source';
    public static string $outputDirectory = 'output';
    public static string $fileExtension = '.md';
    public static string $template = 'template';
}

class ConfigurableSourcesTestPage extends HydePage
{
    use VoidCompiler;

    public static string $sourceDirectory;
    public static string $outputDirectory;
    public static string $fileExtension;
    public static string $template;
}

class DiscoverableTestPage extends HydePage
{
    use VoidCompiler;

    public static string $sourceDirectory = 'foo';
    public static string $outputDirectory = 'bar';
    public static string $fileExtension = 'baz';
    public static string $template;
}

class NonDiscoverableTestPage extends HydePage
{
    use VoidCompiler;

    public static string $sourceDirectory;
    public static string $outputDirectory;
    public static string $fileExtension;
}

class PartiallyDiscoverablePage extends HydePage
{
    use VoidCompiler;

    public static string $sourceDirectory = 'foo';
    public static string $outputDirectory;
    public static string $fileExtension;
}

class DiscoverablePageWithInvalidSourceDirectory extends HydePage
{
    use VoidCompiler;

    public static string $sourceDirectory = '';
    public static string $outputDirectory = '';
    public static string $fileExtension = '';
}

class MissingSourceDirectoryMarkdownPage extends BaseMarkdownPage
{
    public static string $sourceDirectory = 'foo';
}

trait VoidCompiler
{
    public function compile(): string
    {
        return '';
    }
}
