<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Framework\Features\DataCollections\DataCollection;
use Hyde\Testing\UnitTestCase;
use Illuminate\Support\Collection;

/**
 * @covers \Hyde\Framework\Features\DataCollections\DataCollection
 *
 * @see \Hyde\Framework\Testing\Feature\DataCollectionTest
 */
class DataCollectionUnitTest extends UnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::needsKernel();
    }

    public function testClassHasStaticSourceDirectoryProperty()
    {
        $this->assertSame('resources/collections', DataCollection::$sourceDirectory);
    }

    public function testConstructorCreatesNewDataCollectionInstance()
    {
        $class = DataCollection::create('foo');
        $this->assertInstanceOf(DataCollection::class, $class);
        $this->assertInstanceOf(Collection::class, $class);
    }

    public function testConstructorSetsKey()
    {
        $class = DataCollection::create('foo');
        $this->assertSame('foo', $class->key);
    }

    public function testGetCollectionMethodReturnsTheCollectionInstance()
    {
        $class = DataCollection::create('foo');
        $this->assertSame($class, $class->getCollection());
    }

    public function testCanConvertCollectionToArray()
    {
        $class = DataCollection::create('foo');
        $this->assertIsArray($class->toArray());
        $this->assertEmpty($class->toArray());
    }

    public function testStaticMarkdownHelperReturnsNewDataCollectionInstance()
    {
        $this->assertInstanceOf(DataCollection::class, DataCollection::markdown('foo'));
    }
}
