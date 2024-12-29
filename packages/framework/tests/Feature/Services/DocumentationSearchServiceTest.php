<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Services;

use Hyde\Framework\Actions\GeneratesDocumentationSearchIndex;
use Hyde\Hyde;
use Hyde\Testing\CreatesTemporaryFiles;
use Hyde\Testing\UnitTestCase;

/**
 * @covers \Hyde\Framework\Actions\GeneratesDocumentationSearchIndex
 */
class DocumentationSearchServiceTest extends UnitTestCase
{
    use CreatesTemporaryFiles;

    protected function setUp(): void
    {
        self::setupKernel();
        self::mockConfig();
    }

    protected function tearDown(): void
    {
        $this->cleanUpFilesystem();
    }

    public function testItGeneratesAJsonFileWithASearchIndex()
    {
        $this->file('_docs/foo.md');

        $this->assertSame(json_encode([[
            'slug' => 'foo',
            'title' => 'Foo',
            'content' => '',
            'destination' => 'foo.html',
        ]]), GeneratesDocumentationSearchIndex::handle());
    }

    public function testItAddsAllFilesToSearchIndex()
    {
        $this->file('_docs/foo.md');
        $this->file('_docs/bar.md');
        $this->file('_docs/baz.md');

        $this->assertCount(3, $this->getArray());
    }

    public function testItHandlesGenerationEvenWhenThereAreNoPages()
    {
        $this->assertSame('[]', GeneratesDocumentationSearchIndex::handle());
    }

    public function testItGeneratesAValidJson()
    {
        $this->file('_docs/foo.md', "# Bar\nHello World");
        $this->file('_docs/bar.md', "# Foo\n\nHello World");

        $this->assertSame(
            <<<'JSON'
            [{"slug":"bar","title":"Foo","content":"Foo\n\nHello World","destination":"bar.html"},{"slug":"foo","title":"Bar","content":"Bar\nHello World","destination":"foo.html"}]
            JSON,
            json_encode($this->getArray())
        );
    }

    public function testItStripsMarkdown()
    {
        $this->file('_docs/foo.md', "# Foo Bar\n**Hello** _World_");

        $this->assertSame(
            "Foo Bar\nHello World",
            $this->getArray()[0]['content']
        );
    }

    public function testGetDestinationForSlugReturnsEmptyStringForIndexWhenPrettyUrlIsEnabled()
    {
        self::mockConfig(['hyde.pretty_urls' => true]);
        $this->file('_docs/index.md');

        $this->assertSame('',
            $this->getArray()[0]['destination']
        );
    }

    public function testGetDestinationForSlugReturnsPrettyUrlWhenEnabled()
    {
        self::mockConfig(['hyde.pretty_urls' => true]);
        $this->file('_docs/foo.md');

        $this->assertSame('foo',
            $this->getArray()[0]['destination']
        );
    }

    public function testExcludedPagesAreNotPresentInTheSearchIndex()
    {
        $this->file('_docs/excluded.md');
        self::mockConfig(['docs.exclude_from_search' => ['excluded']]);

        $this->assertStringNotContainsString('excluded',
            json_encode($this->getArray())
        );
    }

    public function testNestedSourceFilesDoNotRetainDirectoryNameInSearchIndex()
    {
        $this->directory(Hyde::path('_docs/foo'));
        $this->file('_docs/foo/bar.md');

        $this->assertStringNotContainsString('foo',
            json_encode($this->getArray())
        );
    }

    protected function getArray(): array
    {
        return json_decode(GeneratesDocumentationSearchIndex::handle(), true);
    }
}
