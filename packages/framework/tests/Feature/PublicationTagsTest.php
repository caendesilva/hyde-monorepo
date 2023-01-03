<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Framework\Exceptions\FileNotFoundException;
use Hyde\Framework\Features\Publications\Models\PublicationTags;
use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Illuminate\Support\Collection;

use JsonException;

use function json_encode;

/**
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationTags
 */
class PublicationTagsTest extends TestCase
{
    public function canConstructNewTagsInstance()
    {
        $this->assertInstanceOf(PublicationTags::class, new PublicationTags());
    }

    public function testConstructorAutomaticallyLoadsTagsFile()
    {
        $this->file('tags.json', json_encode(['foo' => ['bar', 'baz']]));

        $this->assertEquals(new Collection(['foo' => ['bar', 'baz']]), (new PublicationTags())->getTags());
    }

    public function testConstructorAddsEmptyArrayWhenThereIsNoTagsFile()
    {
        $this->assertEquals(new Collection(), (new PublicationTags())->getTags());
    }

    public function testCanAddTags()
    {
        $tags = new PublicationTags();
        $tags->addTag('test', ['test1', 'test2']);

        $this->assertEquals(new Collection([
            'test' => ['test1', 'test2'],
        ]), $tags->getTags());
    }

    public function testCanAddTagsWithSingleValue()
    {
        $tags = new PublicationTags();
        $tags->addTag('test', 'test1');

        $this->assertEquals(new Collection([
            'test' => ['test1'],
        ]), $tags->getTags());
    }

    public function testCanSaveTagsToDisk()
    {
        $tags = new PublicationTags();
        $tags->addTag('test', ['test1', 'test2']);
        $tags->save();

        $this->assertSame(
            <<<'JSON'
            {
                "test": [
                    "test1",
                    "test2"
                ]
            }
            JSON, file_get_contents(Hyde::path('tags.json'))
        );

        unlink(Hyde::path('tags.json'));
    }

    public function testGetAllTags()
    {
        $tags = [
            'foo' => [
                'bar',
                'baz',
            ],
        ];
        $this->file('tags.json', json_encode($tags));
        $this->assertSame($tags, PublicationTags::getAllTags()->toArray());
    }

    public function testGetAllTagsWithNoTags()
    {
        $this->assertSame([], PublicationTags::getAllTags()->toArray());
    }

    public function testGetValuesForTagName()
    {
        $tags = [
            'foo' => [
                'bar',
                'baz',
            ],
            'bar' => [
                'baz',
                'qux',
            ],
        ];

        $this->file('tags.json', json_encode($tags));

        $this->assertSame(['bar', 'baz'], PublicationTags::getValuesForTagName('foo'));
    }

    public function testGetValuesForTagNameWithMissingTagName()
    {
        $tags = [
            'foo' => [
                'bar',
                'baz',
            ],
        ];

        $this->file('tags.json', json_encode($tags));

        $this->assertSame([], PublicationTags::getValuesForTagName('bar'));
    }

    public function testGetValuesForTagNameWithNoTags()
    {
        $this->assertSame([], PublicationTags::getValuesForTagName('foo'));
    }

    public function testValidateTagsFileWithValidFile()
    {
        $this->file('tags.json', json_encode(['foo' => ['bar', 'baz']]));

        $this->assertTrue(PublicationTags::validateTagsFile());
    }

    public function testValidateTagsFileWithInvalidFile()
    {
        $this->file('tags.json', 'invalid json');

        $this->expectException(JsonException::class);
        PublicationTags::validateTagsFile();
    }

    public function testValidateTagsFileWithNoFile()
    {
        $this->expectException(FileNotFoundException::class);
        PublicationTags::validateTagsFile();
    }

    public function testValidateTagsFileWithEmptyJsonFile()
    {
        $this->file('tags.json', json_encode([]));

        $this->expectException(JsonException::class);
        PublicationTags::validateTagsFile();
    }
}
