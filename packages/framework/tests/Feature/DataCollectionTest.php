<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use ArgumentCountError;
use Hyde\Facades\Features;
use Hyde\Framework\Features\DataCollections\DataCollection;
use Hyde\Framework\Features\DataCollections\DataCollectionServiceProvider;
use Hyde\Framework\Features\DataCollections\Facades\MarkdownCollection;
use Hyde\Hyde;
use Hyde\Markdown\Models\MarkdownDocument;
use Hyde\Testing\TestCase;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

/**
 * @covers \Hyde\Framework\Features\DataCollections\DataCollection
 * @covers \Hyde\Framework\Features\DataCollections\DataCollectionServiceProvider
 * @covers \Hyde\Framework\Features\DataCollections\Facades\MarkdownCollection
 */
class DataCollectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['hyde.features' => [Features::dataCollections()]]);
        (new DataCollectionServiceProvider($this->app))->boot();
    }

    public function test_constructor_creates_new_data_collection_instance()
    {
        $class = new DataCollection('foo');
        $this->assertInstanceOf(DataCollection::class, $class);
        $this->assertInstanceOf(Collection::class, $class);
    }

    public function test_constructor_sets_key()
    {
        $class = new DataCollection('foo');
        $this->assertEquals('foo', $class->key);
    }

    public function test_key_is_required()
    {
        $this->expectException(ArgumentCountError::class);
        new DataCollection();
    }

    public function test_get_collection_method_returns_the_collection_instance()
    {
        $class = new DataCollection('foo');
        $this->assertSame($class, $class->getCollection());
    }

    public function test_get_markdown_files_method_returns_empty_array_if_the_specified_directory_does_not_exist()
    {
        $class = new DataCollection('foo');
        $this->assertIsArray($class->getMarkdownFiles());
        $this->assertEmpty($class->getMarkdownFiles());
    }

    public function test_get_markdown_files_method_returns_empty_array_if_no_files_are_found_in_specified_directory()
    {
        mkdir(Hyde::path('resources/collections/foo'));
        $class = new DataCollection('foo');
        $this->assertIsArray($class->getMarkdownFiles());
        $this->assertEmpty($class->getMarkdownFiles());
        rmdir(Hyde::path('resources/collections/foo'));
    }

    public function test_get_markdown_files_method_returns_an_array_of_markdown_files_in_the_specified_directory()
    {
        mkdir(Hyde::path('resources/collections/foo'));
        Hyde::touch('resources/collections/foo/foo.md');
        Hyde::touch('resources/collections/foo/bar.md');

        $this->assertEquals([
            Hyde::path('resources/collections/foo/bar.md'),
            Hyde::path('resources/collections/foo/foo.md'),
        ], (new DataCollection('foo'))->getMarkdownFiles());

        File::deleteDirectory(Hyde::path('resources/collections/foo'));
    }

    public function test_get_markdown_files_method_does_not_include_files_in_subdirectories()
    {
        mkdir(Hyde::path('resources/collections/foo'));
        mkdir(Hyde::path('resources/collections/foo/bar'));
        Hyde::touch('resources/collections/foo/foo.md');
        Hyde::touch('resources/collections/foo/bar/bar.md');
        $this->assertEquals([
            Hyde::path('resources/collections/foo/foo.md'),
        ], (new DataCollection('foo'))->getMarkdownFiles());
        File::deleteDirectory(Hyde::path('resources/collections/foo'));
    }

    public function test_get_markdown_files_method_does_not_include_files_with_extensions_other_than_md()
    {
        mkdir(Hyde::path('resources/collections/foo'));
        Hyde::touch('resources/collections/foo/foo.md');
        Hyde::touch('resources/collections/foo/bar.txt');
        $this->assertEquals([
            Hyde::path('resources/collections/foo/foo.md'),
        ], (new DataCollection('foo'))->getMarkdownFiles());
        File::deleteDirectory(Hyde::path('resources/collections/foo'));
    }

    public function test_get_markdown_files_method_does_not_remove_files_starting_with_an_underscore()
    {
        mkdir(Hyde::path('resources/collections/foo'));
        Hyde::touch('resources/collections/foo/_foo.md');

        $this->assertEquals([
            Hyde::path('resources/collections/foo/_foo.md'),
        ], (new DataCollection('foo'))->getMarkdownFiles());
        File::deleteDirectory(Hyde::path('resources/collections/foo'));
    }

    public function test_static_markdown_helper_returns_new_data_collection_instance()
    {
        $this->assertInstanceOf(DataCollection::class, DataCollection::markdown('foo'));
    }

    public function test_static_markdown_helper_discovers_and_parses_markdown_files_in_the_specified_directory()
    {
        mkdir(Hyde::path('resources/collections/foo'));
        Hyde::touch('resources/collections/foo/foo.md');
        Hyde::touch('resources/collections/foo/bar.md');

        $collection = DataCollection::markdown('foo');

        $this->assertContainsOnlyInstancesOf(MarkdownDocument::class, $collection);

        File::deleteDirectory(Hyde::path('resources/collections/foo'));
    }

    public function test_static_markdown_helper_doest_not_ignore_files_starting_with_an_underscore()
    {
        mkdir(Hyde::path('resources/collections/foo'));
        Hyde::touch('resources/collections/foo/foo.md');
        Hyde::touch('resources/collections/foo/_bar.md');
        $this->assertCount(2, DataCollection::markdown('foo'));
        File::deleteDirectory(Hyde::path('resources/collections/foo'));
    }

    public function test_markdown_facade_returns_same_result_as_static_markdown_helper()
    {
        $expected = DataCollection::markdown('foo');
        $actual = MarkdownCollection::get('foo');
        unset($expected->parseTimeInMs);
        unset($actual->parseTimeInMs);
        $this->assertEquals($expected, $actual);
    }

    public function test_data_collection_service_provider_registers_the_facade_as_an_alias()
    {
        $this->assertArrayHasKey('MarkdownCollection', AliasLoader::getInstance()->getAliases());
        $this->assertContains(MarkdownCollection::class, AliasLoader::getInstance()->getAliases());
    }

    public function test_data_collection_service_provider_creates_the__data_directory_if_it_does_not_exist_and_feature_is_enabled()
    {
        config(['hyde.features' => [Features::dataCollections()]]);

        File::deleteDirectory(Hyde::path('resources/collections'));
        $this->assertFileDoesNotExist(Hyde::path('resources/collections'));

        (new DataCollectionServiceProvider($this->app))->boot();

        $this->assertFileExists(Hyde::path('resources/collections'));
    }

    public function test_data_collection_service_provider_does_not_create_the_collections_directory_feature_is_disabled()
    {
        File::deleteDirectory(Hyde::path('resources/collections'));
        $this->assertFileDoesNotExist(Hyde::path('resources/collections'));

        config(['hyde.features' => []]);
        $this->app['config']->set('hyde.data_collection.enabled', false);
        (new DataCollectionServiceProvider($this->app))->boot();

        $this->assertFileDoesNotExist(Hyde::path('resources/collections'));
    }

    public function test_class_has_static_source_directory_property()
    {
        $this->assertEquals('resources/collections', DataCollection::$sourceDirectory);
    }

    public function test_source_directory_can_be_changed()
    {
        DataCollection::$sourceDirectory = 'foo';
        mkdir(Hyde::path('foo/bar'), recursive: true);
        Hyde::touch('foo/bar/foo.md');
        $this->assertEquals([
            Hyde::path('foo/bar/foo.md'),
        ], (new DataCollection('bar'))->getMarkdownFiles());
        File::deleteDirectory(Hyde::path('foo'));
    }
}
