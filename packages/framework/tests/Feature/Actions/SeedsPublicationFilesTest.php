<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Actions;

use Hyde\Framework\Actions\SeedsPublicationFiles;
use Hyde\Framework\Features\Publications\Models\PublicationFieldDefinition;
use Hyde\Framework\Features\Publications\Models\PublicationType;
use Hyde\Hyde;
use Hyde\Markdown\Models\MarkdownDocument;
use Hyde\Testing\TestCase;
use function key;

/**
 * @covers \Hyde\Framework\Actions\SeedsPublicationFiles
 */
class SeedsPublicationFilesTest extends TestCase
{
    protected PublicationType $pubType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory('test-publication');
        $this->setupTestPublication();
        $this->pubType = PublicationType::get('test-publication');
    }

    public function testCreate()
    {
        $action = new SeedsPublicationFiles($this->pubType);
        $action->create();

        $files = $this->getPublicationFiles();
        $this->assertFileExists($files[0]);
        $this->assertCount(1, $files);
    }

    public function testCreateMany()
    {
        $action = new SeedsPublicationFiles($this->pubType, 3);
        $action->create();

        $files = $this->getPublicationFiles();
        $this->assertCount(3, $files);
    }

    public function testWithArrayType()
    {
        $this->updateSchema('array', 'tags');
        (new SeedsPublicationFiles($this->pubType))->create();

        $publication = $this->firstPublication();

        $this->assertBaseline($publication);
        $this->assertNotEmpty($publication->matter('tags'));
        $this->assertIsArray($publication->matter('tags'));
        $this->assertSame(0, key($publication->matter('tags')));
        $this->assertIsString($publication->matter('tags')[0]);
    }

    public function testWithBooleanType()
    {
        $this->updateSchema('boolean', 'published');
        (new SeedsPublicationFiles($this->pubType))->create();

        $publication = $this->firstPublication();

        $this->assertBaseline($publication);
        $this->assertIsBool($publication->matter('published'));
    }

    public function testWithDateTimeType()
    {
        $this->updateSchema('datetime', 'published_at');
        (new SeedsPublicationFiles($this->pubType))->create();

        $publication = $this->firstPublication();

        $this->assertBaseline($publication);
        $this->assertIsString($publication->matter('published_at'));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $publication->matter('published_at'));
    }

    public function testWithFloatType()
    {
        $this->updateSchema('float', 'price');
        (new SeedsPublicationFiles($this->pubType))->create();

        $publication = $this->firstPublication();

        $this->assertBaseline($publication);
        $this->assertIsFloat($publication->matter('price'));
    }

    public function testWithImageType()
    {
        $this->updateSchema('image', 'image');
        (new SeedsPublicationFiles($this->pubType))->create();

        $publication = $this->firstPublication();

        $this->assertBaseline($publication);
        $this->assertIsString($publication->matter('image'));
        $this->assertStringStartsWith('https://picsum.photos/id/', $publication->matter('image'));
    }

    public function testWithIntegerType()
    {
        $this->updateSchema('integer', 'views');
        (new SeedsPublicationFiles($this->pubType))->create();

        $publication = $this->firstPublication();

        $this->assertBaseline($publication);
        $this->assertIsInt($publication->matter('views'));
    }

    public function testWithStringType()
    {
        $this->updateSchema('string', 'title');
        (new SeedsPublicationFiles($this->pubType))->create();

        $publication = $this->firstPublication();

        $this->assertBaseline($publication);
        $this->assertNotEmpty($publication->matter('title'));
    }

    public function testWithTagType()
    {
        $tags = ['test-publication' => ['foo', 'bar', 'baz']];
        $this->file('tags.json', json_encode($tags));
        $this->pubType->fields = collect([
            (new PublicationFieldDefinition('tag', 'tag', tagGroup: 'test-publication')),
        ]);
        $this->pubType->save();
        (new SeedsPublicationFiles($this->pubType))->create();

        $publication = $this->firstPublication();

        $this->assertBaseline($publication);
        $this->assertNotEmpty($publication->matter('tag'));
        $this->assertIsString($publication->matter('tag'));
        $this->assertTrue(in_array($publication->matter('tag'), $tags['test-publication']));
    }

    public function testWithTextType()
    {
        $this->updateSchema('text', 'description');
        (new SeedsPublicationFiles($this->pubType))->create();

        $publication = $this->firstPublication();

        $this->assertBaseline($publication);
        $this->assertNotEmpty($publication->matter('description'));
        $this->assertIsString($publication->matter('description'));
        $this->assertTrue(substr_count($publication->matter('description'), "\n") >= 1);
    }

    public function testWithUrlType()
    {
        $this->updateSchema('url', 'url');
        (new SeedsPublicationFiles($this->pubType))->create();

        $publication = $this->firstPublication();

        $this->assertBaseline($publication);
        $this->assertIsString($publication->matter('url'));
        $this->assertStringStartsWith('http', $publication->matter('url'));
    }

    protected function getPublicationFiles(): array
    {
        $files = glob(Hyde::path('test-publication/*.md'));
        $this->assertNotEmpty($files, 'No publication files found.');

        return $files;
    }

    protected function firstPublication(): MarkdownDocument
    {
        return MarkdownDocument::parse(Hyde::pathToRelative($this->getPublicationFiles()[0]));
    }

    protected function updateSchema(string $type, string $name): void
    {
        $this->pubType->fields = collect([
            (new PublicationFieldDefinition($type, $name)),
        ]);
        $this->pubType->save();
    }

    protected function assertBaseline(MarkdownDocument $publication): void
    {
        $this->assertCount(2, $publication->matter()->toArray());
        $this->assertStringStartsWith('## Write something awesome.', $publication->markdown()->body());
    }
}
