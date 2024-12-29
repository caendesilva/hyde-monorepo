<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Testing\TestCase;
use Hyde\Pages\DocumentationPage;
use Hyde\Framework\Features\Documentation\DocumentationSearchIndex;

/**
 * @covers \Hyde\Framework\Features\Documentation\DocumentationSearchIndex
 *
 * @see \Hyde\Framework\Testing\Feature\Commands\BuildSearchCommandTest
 */
class DocumentationSearchIndexTest extends TestCase
{
    public function testCanCreateDocumentationSearchIndexInstance()
    {
        $this->assertInstanceOf(DocumentationSearchIndex::class, new DocumentationSearchIndex());
    }

    public function testRouteKeyIsSetToDocumentationOutputDirectory()
    {
        $page = new DocumentationSearchIndex();
        $this->assertSame('docs/search.json', $page->routeKey);
    }

    public function testRouteKeyIsSetToConfiguredDocumentationOutputDirectory()
    {
        DocumentationPage::setOutputDirectory('foo');

        $page = new DocumentationSearchIndex();
        $this->assertSame('foo/search.json', $page->routeKey);
    }

    public function testStaticOutputPathHelper()
    {
        $this->assertSame('docs/search.json', DocumentationSearchIndex::outputPath());
    }

    public function testStaticOutputPathHelperWithCustomOutputDirectory()
    {
        DocumentationPage::setOutputDirectory('foo');
        $this->assertSame('foo/search.json', DocumentationSearchIndex::outputPath());
    }

    public function testStaticOutputPathHelperWithRootOutputDirectory()
    {
        DocumentationPage::setOutputDirectory('');
        $this->assertSame('search.json', DocumentationSearchIndex::outputPath());
    }
}
