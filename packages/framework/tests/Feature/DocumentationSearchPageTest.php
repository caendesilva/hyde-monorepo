<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Framework\Features\Documentation\DocumentationSearchPage;
use Hyde\Hyde;
use Hyde\Pages\DocumentationPage;
use Hyde\Pages\InMemoryPage;
use Hyde\Testing\TestCase;

/**
 * @covers \Hyde\Framework\Features\Documentation\DocumentationSearchPage
 *
 * @see \Hyde\Framework\Testing\Feature\Commands\BuildSearchCommandTest
 */
class DocumentationSearchPageTest extends TestCase
{
    public function testCanCreateDocumentationSearchPageInstance()
    {
        $this->assertInstanceOf(DocumentationSearchPage::class, new DocumentationSearchPage());
    }

    public function testRouteKeyIsSetToDocumentationOutputDirectory()
    {
        $page = new DocumentationSearchPage();
        $this->assertSame('docs/search', $page->routeKey);
    }

    public function testRouteKeyIsSetToConfiguredDocumentationOutputDirectory()
    {
        DocumentationPage::setOutputDirectory('foo');

        $page = new DocumentationSearchPage();
        $this->assertSame('foo/search', $page->routeKey);
    }

    public function testEnabledDefaultsToTrue()
    {
        $this->assertTrue(DocumentationSearchPage::enabled());
    }

    public function testEnabledIsFalseWhenDisabled()
    {
        config(['docs.create_search_page' => false]);
        $this->assertFalse(DocumentationSearchPage::enabled());
    }

    public function testEnabledIsFalseWhenRouteExists()
    {
        Hyde::pages()->put('docs/search', new InMemoryPage('docs/search'));
        $this->assertFalse(DocumentationSearchPage::enabled());
    }

    public function testEnabledIsFalseWhenDisabledAndRouteExists()
    {
        config(['docs.create_search_page' => false]);
        Hyde::pages()->put('docs/search', new InMemoryPage('docs/search'));
        $this->assertFalse(DocumentationSearchPage::enabled());
    }

    public function testStaticRouteKeyHelper()
    {
        $this->assertSame('docs/search', DocumentationSearchPage::routeKey());
    }

    public function testStaticRouteKeyHelperWithCustomOutputDirectory()
    {
        DocumentationPage::setOutputDirectory('foo');
        $this->assertSame('foo/search', DocumentationSearchPage::routeKey());
    }

    public function testStaticRouteKeyHelperWithRootOutputDirectory()
    {
        DocumentationPage::setOutputDirectory('');
        $this->assertSame('search', DocumentationSearchPage::routeKey());
    }

    public function testCanRenderSearchPage()
    {
        $page = new DocumentationSearchPage();

        Hyde::shareViewData($page);
        $this->assertStringContainsString('<h1>Search the HydePHP Documentation</h1>', $page->compile());
    }

    public function testRenderedSearchPageUsesDocumentationPageLayout()
    {
        $page = new DocumentationSearchPage();

        Hyde::shareViewData($page);
        $html = $page->compile();

        $this->assertStringContainsString('<body id="hyde-docs"', $html);
    }

    public function testRenderedSearchPageDoesNotUseSemanticDocumentationMarkup()
    {
        $page = new DocumentationSearchPage();

        Hyde::shareViewData($page);
        $html = $page->compile();

        $this->assertStringNotContainsString('<header id="document-header"', $html);
        $this->assertStringNotContainsString('<section id="document-main-content"', $html);
        $this->assertStringNotContainsString('<footer id="document-footer"', $html);
    }

    public function testRenderedSearchPageUsesCustomSidebarHeader()
    {
        config(['docs.sidebar.header' => 'My Project']);
        $page = new DocumentationSearchPage();

        Hyde::shareViewData($page);
        $this->assertStringContainsString('<h1>Search My Project</h1>', $page->compile());
    }

    public function testRenderedSearchPageExpandsDocsInSidebarHeader()
    {
        config(['docs.sidebar.header' => 'Custom Docs']);
        $page = new DocumentationSearchPage();

        Hyde::shareViewData($page);
        $this->assertStringContainsString('<h1>Search the Custom Documentation</h1>', $page->compile());
    }
}
