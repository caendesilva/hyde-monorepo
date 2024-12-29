<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Framework\Actions\Internal\FileFinder;
use Hyde\Testing\UnitTestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Mockery;
use Illuminate\Support\Str;
use Hyde\Support\DataCollection;
use Hyde\Markdown\Models\FrontMatter;
use Hyde\Markdown\Models\MarkdownDocument;
use Hyde\Framework\Exceptions\ParseException;

/**
 * @covers \Hyde\Support\DataCollection
 *
 * @see \Hyde\Framework\Testing\Feature\DataCollectionTest
 */
class DataCollectionUnitTest extends UnitTestCase
{
    protected static bool $needsKernel = true;

    protected function tearDown(): void
    {
        MockableDataCollection::tearDown();
    }

    public function testClassHasStaticSourceDirectoryProperty()
    {
        $this->assertSame('resources/collections', DataCollection::$sourceDirectory);
    }

    public function testConstructorCreatesNewDataCollectionInstance()
    {
        $this->assertInstanceOf(DataCollection::class, new DataCollection());
    }

    public function testClassExtendsCollectionClass()
    {
        $this->assertInstanceOf(Collection::class, new DataCollection());
    }

    public function testCanConvertCollectionToArray()
    {
        $this->assertSame([], (new DataCollection())->toArray());
    }

    public function testCanConvertCollectionToJson()
    {
        $this->assertSame('[]', (new DataCollection())->toJson());
    }

    public function testFindMarkdownFilesWithNoFiles()
    {
        $this->mockFileFinder([]);

        $this->assertSame([], DataCollection::markdown('foo')->keys()->toArray());

        $this->verifyMockeryExpectations();
    }

    public function testFindMarkdownFilesWithFiles()
    {
        $this->mockFileFinder(['bar.md']);

        $this->assertSame(['bar.md'], DataCollection::markdown('foo')->keys()->toArray());

        $this->verifyMockeryExpectations();
    }

    public function testStaticMarkdownHelperReturnsNewDataCollectionInstance()
    {
        $this->assertInstanceOf(DataCollection::class, DataCollection::markdown('foo'));
    }

    public function testMarkdownMethodReturnsCollectionOfMarkdownDocuments()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.md' => 'bar',
            'foo/baz.md' => 'baz',
        ]);

        $this->assertMarkdownCollectionStructure([
            'foo/bar.md' => 'bar',
            'foo/baz.md' => 'baz',
        ], MockableDataCollection::markdown('foo'));
    }

    public function testMarkdownMethodReturnsCollectionOfMarkdownDocumentsWithFrontMatter()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.md' => "---\nfoo: bar\n---\nbar",
            'foo/baz.md' => "---\nfoo: baz\n---\nbaz",
        ]);

        $this->assertMarkdownCollectionStructure([
            'foo/bar.md' => [
                'matter' => ['foo' => 'bar'],
                'content' => 'bar',
            ],
            'foo/baz.md' => [
                'matter' => ['foo' => 'baz'],
                'content' => 'baz',
            ],
        ], MockableDataCollection::markdown('foo'));
    }

    public function testMarkdownMethodReturnsCollectionOfMarkdownDocumentsWithOnlyOneHavingFrontMatter()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.md' => 'bar',
            'foo/baz.md' => "---\nfoo: baz\n---\nbaz",
        ]);

        $this->assertMarkdownCollectionStructure([
            'foo/bar.md' => [
                'matter' => [],
                'content' => 'bar',
            ],
            'foo/baz.md' => [
                'matter' => ['foo' => 'baz'],
                'content' => 'baz',
            ],
        ], MockableDataCollection::markdown('foo'));
    }

    public function testMarkdownMethodWithEmptyFrontMatter()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.md' => "---\n---\nbar",
        ]);

        $this->assertMarkdownCollectionStructure([
            'foo/bar.md' => 'bar',
        ], MockableDataCollection::markdown('foo'));
    }

    public function testMarkdownMethodWithEmptyFileThrowsException()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.md' => '',
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid Markdown in file: 'foo/bar.md' (File is empty)");

        MockableDataCollection::markdown('foo');
    }

    public function testMarkdownMethodWithEmptyFrontMatterAndContentThrowsException()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.md' => "---\n---",
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid Markdown in file: 'foo/bar.md' (File is empty)");

        MockableDataCollection::markdown('foo');
    }

    public function testMarkdownMethodWithEmptyContentIsAcceptableIfFrontMatterIsSet()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.md' => "---\nfoo: bar\n---",
        ]);

        $this->assertMarkdownCollectionStructure([
            'foo/bar.md' => [
                'matter' => ['foo' => 'bar'],
                'content' => '',
            ],
        ], MockableDataCollection::markdown('foo'));
    }

    public function testMarkdownMethodWithUnterminatedFrontMatter()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.md' => "---\nfoo: bar\nbar",
        ]);

        $this->assertMarkdownCollectionStructure([
            'foo/bar.md' => "---\nfoo: bar\nbar",
        ], MockableDataCollection::markdown('foo'));
    }

    public function testMarkdownMethodWithUninitializedFrontMatter()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.md' => "foo: bar\n---\nbar",
        ]);

        $this->assertMarkdownCollectionStructure([
            'foo/bar.md' => "foo: bar\n---\nbar",
        ], MockableDataCollection::markdown('foo'));
    }

    public function testMarkdownMethodWithInvalidFrontMatter()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.md' => "---\nfoo: 'bar\n---\nbar",
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid Markdown in file: 'foo/bar.md' (Malformed inline YAML string at line 1 (near \"foo: 'bar\"))");

        MockableDataCollection::markdown('foo');
    }

    public function testYamlMethodReturnsCollectionOfFrontMatterObjects()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.yml' => "---\nfoo: bar\n---",
            'foo/baz.yml' => "---\nfoo: baz\n---",
        ]);

        $this->assertFrontMatterCollectionStructure([
            'foo/bar.yml' => ['foo' => 'bar'],
            'foo/baz.yml' => ['foo' => 'baz'],
        ], MockableDataCollection::yaml('foo'));
    }

    public function testYamlCollectionsDoNotRequireTripleDashes()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.yml' => 'foo: bar',
            'foo/baz.yml' => 'foo: baz',
        ]);

        $this->assertFrontMatterCollectionStructure([
            'foo/bar.yml' => ['foo' => 'bar'],
            'foo/baz.yml' => ['foo' => 'baz'],
        ], MockableDataCollection::yaml('foo'));
    }

    public function testYamlCollectionsAcceptTripleDashes()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.yml' => "---\nfoo: bar\n---",
            'foo/baz.yml' => "---\nfoo: baz",
        ]);

        $this->assertFrontMatterCollectionStructure([
            'foo/bar.yml' => ['foo' => 'bar'],
            'foo/baz.yml' => ['foo' => 'baz'],
        ], MockableDataCollection::yaml('foo'));
    }

    public function testYamlCollectionsSupportYamlAndYmlFileExtensions()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.yaml' => "---\nfoo: bar\n---",
            'foo/baz.yml' => "---\nfoo: baz\n---",
        ]);

        $this->assertFrontMatterCollectionStructure([
            'foo/bar.yaml' => ['foo' => 'bar'],
            'foo/baz.yml' => ['foo' => 'baz'],
        ], MockableDataCollection::yaml('foo'));
    }

    public function testYamlCollectionsHandleLeadingAndTrailingNewlines()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.yml' => "\nfoo: bar\n",
            'foo/baz.yml' => "\nfoo: baz",
            'foo/qux.yml' => "foo: qux\n",
        ]);

        $this->assertFrontMatterCollectionStructure([
            'foo/bar.yml' => ['foo' => 'bar'],
            'foo/baz.yml' => ['foo' => 'baz'],
            'foo/qux.yml' => ['foo' => 'qux'],
        ], MockableDataCollection::yaml('foo'));
    }

    public function testYamlCollectionsHandleTrailingWhitespace()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.yml' => 'foo: bar ',
            'foo/baz.yml' => 'foo: baz  ',
        ]);

        $this->assertFrontMatterCollectionStructure([
            'foo/bar.yml' => ['foo' => 'bar'],
            'foo/baz.yml' => ['foo' => 'baz'],
        ], MockableDataCollection::yaml('foo'));
    }

    public function testYamlCollectionsHandleLeadingAndTrailingNewlinesAndTrailingWhitespace()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.yml' => "\nfoo: bar  \n",
            'foo/baz.yml' => "\nfoo: baz\n ",
            'foo/qux.yml' => "foo: qux  \n",
        ]);

        $this->assertFrontMatterCollectionStructure([
            'foo/bar.yml' => ['foo' => 'bar'],
            'foo/baz.yml' => ['foo' => 'baz'],
            'foo/qux.yml' => ['foo' => 'qux'],
        ], MockableDataCollection::yaml('foo'));
    }

    public function testYamlCollectionsThrowExceptionForInvalidYaml()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.yml' => "---\nfoo: 'bar",
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid Yaml in file: 'foo/bar.yml' (Malformed inline YAML string at line 2 (near \"foo: 'bar\"))");

        MockableDataCollection::yaml('foo');
    }

    public function testYamlCollectionsThrowExceptionForEmptyYaml()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.yml' => '',
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid Yaml in file: 'foo/bar.yml' (File is empty)");

        MockableDataCollection::yaml('foo');
    }

    public function testYamlCollectionsThrowExceptionForBlankYaml()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.yml' => ' ',
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid Yaml in file: 'foo/bar.yml' (File is empty)");

        MockableDataCollection::yaml('foo');
    }

    public function testYamlCollectionsThrowExceptionForOtherReasonsThanSyntaxErrorWithUtfError()
    {
        MockableDataCollection::mockFiles([
            'foo/utf.yml' => "foo: \xB1\x31",
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid Yaml in file: 'foo/utf.yml' (The YAML value does not appear to be valid UTF-8)");

        MockableDataCollection::yaml('foo');
    }

    public function testYamlCollectionsThrowExceptionForOtherReasonsThanSyntaxErrorWithTabsError()
    {
        MockableDataCollection::mockFiles([
            'foo/tabs.yml' => "foo:\n\tbar",
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid Yaml in file: 'foo/tabs.yml' (A YAML file cannot contain tabs as indentation at line 2 (near \"	bar\"))");

        MockableDataCollection::yaml('foo');
    }

    public function testJsonMethodReturnsCollectionOfJsonDecodedObjects()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.json' => '{"foo": "bar"}',
            'foo/baz.json' => '{"foo": "baz"}',
        ]);

        $this->assertJsonCollectionStructure([
            'foo/bar.json' => (object) ['foo' => 'bar'],
            'foo/baz.json' => (object) ['foo' => 'baz'],
        ], MockableDataCollection::json('foo'));
    }

    public function testJsonMethodReturnsCollectionOfJsonDecodedArrays()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.json' => '{"foo": "bar"}',
            'foo/baz.json' => '{"foo": "baz"}',
        ]);

        $this->assertJsonCollectionStructure([
            'foo/bar.json' => ['foo' => 'bar'],
            'foo/baz.json' => ['foo' => 'baz'],
        ], MockableDataCollection::json('foo', true), true);
    }

    public function testJsonMethodThrowsExceptionForInvalidJson()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.json' => '{"foo": "bar"',
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid Json in file: 'foo/bar.json' (Syntax error)");

        MockableDataCollection::json('foo');
    }

    public function testJsonMethodThrowsExceptionForInvalidJsonWithArray()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.json' => '{"foo": "bar"',
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid Json in file: 'foo/bar.json' (Syntax error)");

        MockableDataCollection::json('foo', true);
    }

    public function testJsonMethodThrowsExceptionForEmptyJson()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.json' => '',
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid Json in file: 'foo/bar.json' (Syntax error)");

        MockableDataCollection::json('foo');
    }

    public function testJsonMethodThrowsExceptionForBlankJson()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.json' => ' ',
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid Json in file: 'foo/bar.json' (Syntax error)");

        MockableDataCollection::json('foo');
    }

    public function testJsonMethodThrowsExceptionWhenJustOneFileIsInvalid()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.json' => '{"foo": "bar"}',
            'foo/baz.json' => '{"foo": "baz"',
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid Json in file: 'foo/baz.json' (Syntax error)");

        MockableDataCollection::json('foo');
    }

    public function testJsonMethodThrowsExceptionForOtherReasonsThanSyntaxErrorWithUtfError()
    {
        MockableDataCollection::mockFiles([
            'foo/utf.json' => "\xB1\x31",
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid Json in file: 'foo/utf.json' (Malformed UTF-8 characters, possibly incorrectly encoded)");

        MockableDataCollection::json('foo');
    }

    public function testJsonMethodThrowsExceptionForOtherReasonsThanSyntaxErrorWithControlCharacterError()
    {
        MockableDataCollection::mockFiles([
            'foo/control.json' => "\x19\x31",
        ]);

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage("Invalid Json in file: 'foo/control.json' (Control character error, possibly incorrectly encoded)");

        MockableDataCollection::json('foo');
    }

    protected function mockFileFinder(array $files): void
    {
        $filesystem = Mockery::mock(Filesystem::class);
        $filesystem->shouldReceive('exists')->andReturn(true);
        $filesystem->shouldReceive('get')->andReturn('foo');

        app()->instance(Filesystem::class, $filesystem);

        $finder = Mockery::mock(FileFinder::class);
        $finder->shouldReceive('handle')->andReturn(collect($files));

        app()->instance(FileFinder::class, $finder);
    }

    protected function verifyMockeryExpectations(): void
    {
        parent::verifyMockeryExpectations();

        app()->forgetInstance(Filesystem::class);
        app()->forgetInstance(FileFinder::class);
    }

    protected function assertMarkdownCollectionStructure(array $expected, DataCollection $collection): void
    {
        $this->assertContainsOnlyInstancesOf(MarkdownDocument::class, $collection);

        if ($collection->contains(fn (MarkdownDocument $document) => filled($document->matter()->toArray()))) {
            $expected = collect($expected)->map(fn ($value) => is_array($value) ? [
                'matter' => $value['matter'],
                'content' => $value['content'],
            ] : (string) $value)->all();

            $collection = $collection->map(fn (MarkdownDocument $document) => [
                'matter' => $document->matter()->toArray(),
                'content' => $document->markdown()->body(),
            ]);

            $this->assertSame($expected, $collection->all());
        } else {
            $this->assertSame($expected, $collection->map(fn ($value) => (string) $value)->all());
        }
    }

    protected function assertFrontMatterCollectionStructure(array $expected, DataCollection $collection): void
    {
        $this->assertContainsOnlyInstancesOf(FrontMatter::class, $collection);

        $this->assertSame($expected, $collection->map(fn ($value) => $value->toArray())->all());
    }

    protected function assertJsonCollectionStructure(array $expected, DataCollection $collection, bool $asArray = false): void
    {
        if ($asArray) {
            $this->assertContainsOnly('array', $collection);
        } else {
            $this->assertContainsOnly('object', $collection);

            $expected = collect($expected)->map(fn ($value) => (array) $value)->all();
            $collection = $collection->map(fn ($value) => (array) $value);
        }

        $this->assertSame($expected, $collection->all());
    }
}

class MockableDataCollection extends DataCollection
{
    protected static array $mockFiles = [];

    protected static function findFiles(string $name, array|string $extensions): Collection
    {
        return collect(static::$mockFiles)->keys()->map(fn ($file) => parent::makeIdentifier($file))->values();
    }

    /**
     * @param  array<string, string>  $files  Filename as key, file contents as value.
     */
    public static function mockFiles(array $files): void
    {
        foreach ($files as $file => $contents) {
            assert(is_string($file), 'File name must be a string.');
            assert(is_string($contents), 'File contents must be a string.');
            assert(str_contains($file, '/'), 'File must be in a directory.');
            assert(str_contains($file, '.'), 'File must have an extension.');
        }

        $filesystem = Mockery::mock(Filesystem::class);
        $filesystem->shouldReceive('get')
            ->andReturnUsing(function (string $file) use ($files) {
                $file = Str::before(basename($file), '.');
                $files = collect($files)->mapWithKeys(fn ($contents, $file) => [Str::before(basename($file), '.') => $contents])->all();

                return $files[$file] ?? '';
            });

        app()->instance(Filesystem::class, $filesystem);

        static::$mockFiles = $files;
    }

    public static function tearDown(): void
    {
        static::$mockFiles = [];
    }
}
