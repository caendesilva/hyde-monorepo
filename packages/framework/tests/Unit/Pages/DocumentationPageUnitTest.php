<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit\Pages;

use Hyde\Hyde;
use Hyde\Facades\Filesystem;
use Hyde\Support\Models\Route;
use Hyde\Pages\DocumentationPage;
use Hyde\Markdown\Models\Markdown;
use Hyde\Markdown\Models\FrontMatter;
use Hyde\Foundation\Kernel\PageCollection;
use Hyde\Testing\Common\BaseMarkdownPageUnitTest;
use Hyde\Framework\Factories\Concerns\CoreDataObject;
use Hyde\Framework\Features\Metadata\PageMetadataBag;

/**
 * @covers \Hyde\Pages\DocumentationPage
 */
class DocumentationPageUnitTest extends BaseMarkdownPageUnitTest
{
    public function testSourceDirectory()
    {
        $this->assertSame('_docs', DocumentationPage::sourceDirectory());
    }

    public function testOutputDirectory()
    {
        $this->assertSame('docs', DocumentationPage::outputDirectory());
    }

    public function testBaseRouteKey()
    {
        $this->assertSame('docs', DocumentationPage::baseRouteKey());
    }

    public function testFileExtension()
    {
        $this->assertSame('.md', DocumentationPage::fileExtension());
    }

    public function testSourcePath()
    {
        $this->assertSame('_docs/hello-world.md', DocumentationPage::sourcePath('hello-world'));
    }

    public function testOutputPath()
    {
        $this->assertSame('docs/hello-world.html', DocumentationPage::outputPath('hello-world'));
    }

    public function testPath()
    {
        $this->assertSame(Hyde::path('_docs/hello-world.md'), DocumentationPage::path('hello-world.md'));
    }

    public function testGetSourcePath()
    {
        $this->assertSame('_docs/hello-world.md', (new DocumentationPage('hello-world'))->getSourcePath());
    }

    public function testGetOutputPath()
    {
        $this->assertSame('docs/hello-world.html', (new DocumentationPage('hello-world'))->getOutputPath());
    }

    public function testGetLink()
    {
        $this->assertSame('docs/hello-world.html', (new DocumentationPage('hello-world'))->getLink());
    }

    public function testMake()
    {
        $this->assertEquals(DocumentationPage::make(), new DocumentationPage());
    }

    public function testMakeWithData()
    {
        $this->assertEquals(DocumentationPage::make('foo', ['foo' => 'bar']), new DocumentationPage('foo', ['foo' => 'bar']));
    }

    public function testShowInNavigation()
    {
        $this->assertTrue((new DocumentationPage())->showInNavigation());
    }

    public function testNavigationMenuPriority()
    {
        $this->assertSame(999, (new DocumentationPage())->navigationMenuPriority());
    }

    public function testNavigationMenuLabel()
    {
        $this->assertSame('Foo', (new DocumentationPage('foo'))->navigationMenuLabel());
    }

    public function testNavigationMenuGroup()
    {
        $this->assertNull((new DocumentationPage('foo'))->navigationMenuGroup());
    }

    public function testNavigationMenuGroupWithData()
    {
        $this->assertSame('foo', DocumentationPage::make(matter: ['navigation' => ['group' => 'foo']])->navigationMenuGroup());
    }

    public function testGetBladeView()
    {
        $this->assertSame('hyde::layouts/docs', (new DocumentationPage('foo'))->getBladeView());
    }

    public function testFiles()
    {
        $this->assertSame([], DocumentationPage::files());
    }

    public function testData()
    {
        $this->assertSame('foo', (new DocumentationPage('foo'))->data('identifier'));
    }

    public function testGet()
    {
        $this->file(DocumentationPage::sourcePath('foo'));
        $this->assertEquals(new DocumentationPage('foo'), DocumentationPage::get('foo'));
    }

    public function testParse()
    {
        $this->file(DocumentationPage::sourcePath('foo'));
        $this->assertInstanceOf(DocumentationPage::class, DocumentationPage::parse('foo'));
    }

    public function testGetRouteKey()
    {
        $this->assertSame('docs/foo', (new DocumentationPage('foo'))->getRouteKey());
    }

    public function testTitle()
    {
        $documentationPage = new DocumentationPage('foo');
        $this->assertSame('HydePHP - Foo', $documentationPage->title());
    }

    public function testAll()
    {
        $this->assertInstanceOf(PageCollection::class, DocumentationPage::all());
    }

    public function testMetadata()
    {
        $this->assertInstanceOf(PageMetadataBag::class, (new DocumentationPage())->metadata());
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(DocumentationPage::class, new DocumentationPage());
    }

    public function testGetRoute()
    {
        $this->assertInstanceOf(Route::class, (new DocumentationPage())->getRoute());
    }

    public function testGetIdentifier()
    {
        $this->assertSame('foo', (new DocumentationPage('foo'))->getIdentifier());
    }

    public function testHas()
    {
        $this->assertTrue((new DocumentationPage('foo'))->has('identifier'));
    }

    public function testToCoreDataObject()
    {
        $this->assertInstanceOf(CoreDataObject::class, (new DocumentationPage('foo'))->toCoreDataObject());
    }

    public function testCompile()
    {
        $page = new DocumentationPage('foo');
        Hyde::shareViewData($page);
        $this->assertIsString(DocumentationPage::class, $page->compile());
    }

    public function testMatter()
    {
        $this->assertInstanceOf(FrontMatter::class, (new DocumentationPage('foo'))->matter());
    }

    public function testMarkdown()
    {
        $this->assertInstanceOf(Markdown::class, (new DocumentationPage('foo'))->markdown());
    }

    public function testSave()
    {
        $page = new DocumentationPage('foo');
        $this->assertSame($page, $page->save());
        $this->assertFileExists('_docs/foo.md');
        Filesystem::unlink('_docs/foo.md');
    }

    public function testGetCanonicalUrl()
    {
        $page = new DocumentationPage('foo');
        $this->assertNull($page->getCanonicalUrl());

        self::mockConfig(['hyde.url' => 'https://example.com']);

        $this->assertSame('https://example.com/docs/foo.html', $page->getCanonicalUrl());

        self::mockConfig(['hyde.url' => 'https://example.com', 'hyde.pretty_urls' => true]);

        $this->assertSame('https://example.com/docs/foo', $page->getCanonicalUrl());

        $page = new DocumentationPage('foo', ['canonicalUrl' => 'foo']);
        $this->assertSame('foo', $page->getCanonicalUrl());
    }
}
