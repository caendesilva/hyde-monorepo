<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Markdown\Models\FrontMatter;
use Hyde\Markdown\Models\MarkdownDocument;
use Hyde\Support\DataCollection;
use Hyde\Testing\TestCase;

/**
 * @covers \Hyde\Support\DataCollection
 *
 * @see \Hyde\Framework\Testing\Unit\DataCollectionUnitTest
 */
class DataCollectionTest extends TestCase
{
    public function testMarkdownCollections()
    {
        $this->directory('resources/collections/foo');
        $this->markdown('resources/collections/foo/foo.md', 'Hello World', ['title' => 'Foo']);
        $this->file('resources/collections/foo/bar.md', 'Foo');

        $this->assertEquals(new DataCollection([
            'foo/foo.md' => new MarkdownDocument(['title' => 'Foo'], 'Hello World'),
            'foo/bar.md' => new MarkdownDocument([], 'Foo'),
        ]), DataCollection::markdown('foo'));
    }

    public function testYamlCollections()
    {
        $this->directory('resources/collections/foo');
        $this->markdown('resources/collections/foo/foo.yaml', matter: ['title' => 'Foo']);
        $this->file('resources/collections/foo/bar.yml', "---\ntitle: Bar\n---");

        $this->assertEquals(new DataCollection([
            'foo/foo.yaml' => new FrontMatter(['title' => 'Foo']),
            'foo/bar.yml' => new FrontMatter(['title' => 'Bar']),
        ]), DataCollection::yaml('foo'));
    }

    public function testYamlCollectionsWithoutTripleDashes()
    {
        $this->directory('resources/collections/foo');
        $this->file('resources/collections/foo/foo.yml', 'title: Foo');

        $this->assertEquals(new DataCollection([
            'foo/foo.yml' => new FrontMatter(['title' => 'Foo']),
        ]), DataCollection::yaml('foo'));
    }

    public function testJsonCollections()
    {
        $this->directory('resources/collections/foo');
        $this->file('resources/collections/foo/foo.json', json_encode(['foo' => 'bar']));
        $this->file('resources/collections/foo/bar.json', '{"bar": "baz"}');

        $this->assertEquals(new DataCollection([
            'foo/foo.json' => (object) ['foo' => 'bar'],
            'foo/bar.json' => (object) ['bar' => 'baz'],
        ]), DataCollection::json('foo'));
    }

    public function testJsonCollectionsAsArrays()
    {
        $this->directory('resources/collections/foo');
        $this->file('resources/collections/foo/foo.json', json_encode(['foo' => 'bar']));
        $this->file('resources/collections/foo/bar.json', '{"bar": "baz"}');

        $this->assertEquals(new DataCollection([
            'foo/foo.json' => ['foo' => 'bar'],
            'foo/bar.json' => ['bar' => 'baz'],
        ]), DataCollection::json('foo', true));
    }

    public function testFindMarkdownFilesMethodReturnsEmptyArrayIfTheSpecifiedDirectoryDoesNotExist()
    {
        $this->assertIsArray(DataCollection::markdown('foo')->keys()->toArray());
        $this->assertEmpty(DataCollection::markdown('foo')->keys()->toArray());
    }

    public function testFindMarkdownFilesMethodReturnsEmptyArrayIfNoFilesAreFoundInSpecifiedDirectory()
    {
        $this->directory('resources/collections/foo');

        $this->assertIsArray(DataCollection::markdown('foo')->keys()->toArray());
        $this->assertEmpty(DataCollection::markdown('foo')->keys()->toArray());
    }

    public function testFindMarkdownFilesMethodReturnsAnArrayOfMarkdownFilesInTheSpecifiedDirectory()
    {
        $this->directory('resources/collections/foo');
        $this->file('resources/collections/foo/foo.md', 'Foo');
        $this->file('resources/collections/foo/bar.md', 'Bar');

        $this->assertSame([
            'foo/bar.md',
            'foo/foo.md',
        ], DataCollection::markdown('foo')->keys()->toArray());
    }

    public function testFindMarkdownFilesMethodDoesNotIncludeFilesInSubdirectories()
    {
        $this->directory('resources/collections/foo');
        $this->directory('resources/collections/foo/bar');
        $this->file('resources/collections/foo/foo.md', 'Foo');
        $this->file('resources/collections/foo/bar/bar.md', 'Bar');

        $this->assertSame([
            'foo/foo.md',
        ], DataCollection::markdown('foo')->keys()->toArray());
    }

    public function testFindMarkdownFilesMethodDoesNotIncludeFilesWithExtensionsOtherThanMd()
    {
        $this->directory('resources/collections/foo');
        $this->file('resources/collections/foo/foo.md', 'Foo');
        $this->file('resources/collections/foo/bar.txt', 'Bar');

        $this->assertSame([
            'foo/foo.md',
        ], DataCollection::markdown('foo')->keys()->toArray());
    }

    public function testFindMarkdownFilesMethodDoesNotRemoveFilesStartingWithAnUnderscore()
    {
        $this->directory('resources/collections/foo');
        $this->file('resources/collections/foo/_foo.md', 'Foo');

        $this->assertSame([
            'foo/_foo.md',
        ], DataCollection::markdown('foo')->keys()->toArray());
    }

    public function testStaticMarkdownHelperDiscoversAndParsesMarkdownFilesInTheSpecifiedDirectory()
    {
        $this->directory('resources/collections/foo');
        $this->file('resources/collections/foo/foo.md', 'Foo');
        $this->file('resources/collections/foo/bar.md', 'Bar');

        $this->assertEquals([
            'foo/foo.md' => new MarkdownDocument([], 'Foo'),
            'foo/bar.md' => new MarkdownDocument([], 'Bar'),
        ], DataCollection::markdown('foo')->toArray());
    }

    public function testStaticMarkdownHelperDoestNotIgnoreFilesStartingWithAnUnderscore()
    {
        $this->directory('resources/collections/foo');
        $this->file('resources/collections/foo/foo.md', 'Foo');
        $this->file('resources/collections/foo/_bar.md', 'Bar');

        $this->assertCount(2, DataCollection::markdown('foo'));
    }

    public function testSourceDirectoryCanBeChanged()
    {
        DataCollection::$sourceDirectory = 'foo';
        $this->directory('foo/bar');
        $this->file('foo/bar/foo.md', 'Foo');

        $this->assertSame([
            'bar/foo.md',
        ], DataCollection::markdown('bar')->keys()->toArray());

        DataCollection::$sourceDirectory = 'resources/collections';
    }
}
