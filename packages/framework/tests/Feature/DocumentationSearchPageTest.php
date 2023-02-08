<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Framework\Features\Documentation\DocumentationSearchPage;
use Hyde\Hyde;
use Hyde\Pages\DocumentationPage;
use Hyde\Pages\VirtualPage;
use Hyde\Testing\TestCase;

/**
 * @covers \Hyde\Framework\Features\Documentation\DocumentationSearchPage
 */
class DocumentationSearchPageTest extends TestCase
{
    public function testCanCreateDocumentationSearchPageInstance()
    {
        $this->assertInstanceOf(DocumentationSearchPage::class, new DocumentationSearchPage());
    }

    public function testIdentifierIsSetToDocumentationOutputDirectory()
    {
        $page = new DocumentationSearchPage();
        $this->assertSame('docs/search', $page->identifier);
    }

    public function testIdentifierIsSetToConfiguredDocumentationOutputDirectory()
    {
        DocumentationPage::$outputDirectory = 'foo';

        $page = new DocumentationSearchPage();
        $this->assertSame('foo/search', $page->identifier);
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
        Hyde::routes()->put('docs/search', new VirtualPage('docs/search'));
        $this->assertFalse(DocumentationSearchPage::enabled());
    }

    public function testEnabledIsFalseWhenDisabledAndRouteExists()
    {
        config(['docs.create_search_page' => false]);
        Hyde::routes()->put('docs/search', new VirtualPage('docs/search'));
        $this->assertFalse(DocumentationSearchPage::enabled());
    }
}
