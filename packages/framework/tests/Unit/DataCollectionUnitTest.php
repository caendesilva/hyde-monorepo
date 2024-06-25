<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Hyde;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Hyde\Support\DataCollection;
use Hyde\Testing\UnitTestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Mockery;
use Hyde\Markdown\Models\FrontMatter;
use Hyde\Markdown\Models\MarkdownDocument;
use Symfony\Component\Yaml\Exception\ParseException;

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

        parent::tearDown();
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

    public function testFindMarkdownFilesCallsProperGlobPattern()
    {
        $filesystem = Mockery::mock(Filesystem::class, ['exists' => true]);
        $filesystem->shouldReceive('glob')
            ->with(Hyde::path('resources/collections/foo/*.{md}'), GLOB_BRACE)
            ->once();

        app()->instance(Filesystem::class, $filesystem);

        DataCollection::markdown('foo')->keys()->toArray();

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());
        Mockery::close();
    }

    public function testFindMarkdownFilesWithNoFiles()
    {
        $filesystem = Mockery::mock(Filesystem::class, [
            'exists' => true,
            'glob' => [],
        ]);

        app()->instance(Filesystem::class, $filesystem);

        $this->assertSame([], DataCollection::markdown('foo')->keys()->toArray());

        Mockery::close();
    }

    public function testFindMarkdownFilesWithFiles()
    {
        $filesystem = Mockery::mock(Filesystem::class, [
            'exists' => true,
            'glob' => ['bar.md'],
            'get' => 'foo',
        ]);

        app()->instance(Filesystem::class, $filesystem);

        $this->assertSame(['bar.md'], DataCollection::markdown('foo')->keys()->toArray());

        Mockery::close();
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

    public function testMarkdownMethodWithEmptyFrontMatterAndContent()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.md' => "---\n---",
        ]);

        $this->assertMarkdownCollectionStructure([
            'foo/bar.md' => '',
        ], MockableDataCollection::markdown('foo'));
    }

    public function testMarkdownMethodWithEmptyContent()
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

    public function testMarkdownMethodWithEmptyFile()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.md' => '',
        ]);

        $this->assertMarkdownCollectionStructure([
            'foo/bar.md' => '',
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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid YAML in file: 'foo/bar.yml' (Malformed inline YAML string at line 2 (near \"foo: 'bar\"))");

        MockableDataCollection::yaml('foo');
    }

    public function testYamlCollectionsThrowExceptionForEmptyYaml()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.yml' => '',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid YAML in file: 'foo/bar.yml' (File is empty)");

        MockableDataCollection::yaml('foo');
    }

    public function testYamlCollectionsThrowExceptionForBlankYaml()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.yml' => ' ',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid YAML in file: 'foo/bar.yml' (File is empty)");

        MockableDataCollection::yaml('foo');
    }

    public function testYamlCollectionsThrowExceptionForOtherReasonsThanSyntaxErrorWithUtfError()
    {
        MockableDataCollection::mockFiles([
            'foo/utf.yml' => "foo: \xB1\x31",
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid YAML in file: 'foo/utf.yml' (The YAML value does not appear to be valid UTF-8)");

        MockableDataCollection::yaml('foo');
    }

    public function testYamlCollectionsThrowExceptionForOtherReasonsThanSyntaxErrorWithTabsError()
    {
        MockableDataCollection::mockFiles([
            'foo/tabs.yml' => "foo:\n\tbar",
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid YAML in file: 'foo/tabs.yml' (A YAML file cannot contain tabs as indentation at line 2 (near \"	bar\"))");

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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid JSON in file: 'foo/bar.json' (Syntax error)");

        MockableDataCollection::json('foo');
    }

    public function testJsonMethodThrowsExceptionForInvalidJsonWithArray()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.json' => '{"foo": "bar"',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid JSON in file: 'foo/bar.json' (Syntax error)");

        MockableDataCollection::json('foo', true);
    }

    public function testJsonMethodThrowsExceptionForEmptyJson()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.json' => '',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid JSON in file: 'foo/bar.json' (Syntax error)");

        MockableDataCollection::json('foo');
    }

    public function testJsonMethodThrowsExceptionForBlankJson()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.json' => ' ',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid JSON in file: 'foo/bar.json' (Syntax error)");

        MockableDataCollection::json('foo');
    }

    public function testJsonMethodThrowsExceptionWhenJustOneFileIsInvalid()
    {
        MockableDataCollection::mockFiles([
            'foo/bar.json' => '{"foo": "bar"}',
            'foo/baz.json' => '{"foo": "baz"',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid JSON in file: 'foo/baz.json' (Syntax error)");

        MockableDataCollection::json('foo');
    }

    public function testJsonMethodThrowsExceptionForOtherReasonsThanSyntaxErrorWithUtfError()
    {
        MockableDataCollection::mockFiles([
            'foo/utf.json' => "\xB1\x31",
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid JSON in file: 'foo/utf.json' (Malformed UTF-8 characters, possibly incorrectly encoded)");

        MockableDataCollection::json('foo');
    }

    public function testJsonMethodThrowsExceptionForOtherReasonsThanSyntaxErrorWithControlCharacterError()
    {
        MockableDataCollection::mockFiles([
            'foo/control.json' => "\x19\x31",
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid JSON in file: 'foo/control.json' (Control character error, possibly incorrectly encoded)");

        MockableDataCollection::json('foo');
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
