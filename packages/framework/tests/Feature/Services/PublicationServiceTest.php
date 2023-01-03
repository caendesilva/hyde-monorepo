<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Services;

use function copy;
use function file_put_contents;
use Hyde\Framework\Exceptions\FileNotFoundException;
use Hyde\Framework\Features\Publications\Models\PublicationType;
use Hyde\Framework\Features\Publications\PublicationService;
use Hyde\Hyde;
use Hyde\Pages\PublicationPage;
use Hyde\Testing\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use function json_encode;
use function mkdir;

/**
 * @covers \Hyde\Framework\Features\Publications\PublicationService
 */
class PublicationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        mkdir(Hyde::path('test-publication'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(Hyde::path('test-publication'));

        parent::tearDown();
    }

    public function testGetPublicationTypes()
    {
        $this->assertEquals(new Collection(), PublicationService::getPublicationTypes());
    }

    public function testGetPublicationTypesWithTypes()
    {
        $this->createPublicationType();

        $this->assertEquals(new Collection([
            'test-publication' => PublicationType::get('test-publication'),
        ]), PublicationService::getPublicationTypes());
    }

    public function testGetPublicationsForPubType()
    {
        $this->createPublicationType();

        $this->assertEquals(
            new Collection(),
            PublicationService::getPublicationsForPubType(PublicationType::get('test-publication'))
        );
    }

    public function testGetPublicationsForPubTypeWithPublications()
    {
        $this->createPublicationType();
        $this->createPublication();

        $this->assertEquals(
            new Collection([
                PublicationService::parsePublicationFile('test-publication/foo.md'),
            ]),
            PublicationService::getPublicationsForPubType(PublicationType::get('test-publication'))
        );
    }

    public function testGetPublicationsForPubTypeOnlyContainsInstancesOfPublicationPage()
    {
        $this->createPublicationType();
        $this->createPublication();

        $this->assertContainsOnlyInstancesOf(
            PublicationPage::class,
            PublicationService::getPublicationsForPubType(PublicationType::get('test-publication'))
        );
    }

    public function testGetMediaForPubType()
    {
        $this->createPublicationType();

        $this->assertEquals(
            new Collection(),
            PublicationService::getMediaForPubType(PublicationType::get('test-publication'))
        );
    }

    public function testGetMediaForPubTypeWithMedia()
    {
        $this->createPublicationType();
        mkdir(Hyde::path('_media/test-publication'));
        file_put_contents(Hyde::path('_media/test-publication/image.png'), '');

        $this->assertEquals(
            new Collection([
                '_media/test-publication/image.png',
            ]),
            PublicationService::getMediaForPubType(PublicationType::get('test-publication'))
        );

        File::deleteDirectory(Hyde::path('_media/test-publication'));
    }

    public function testParsePublicationFile()
    {
        $this->createPublicationType();
        $this->createPublication();

        $file = PublicationService::parsePublicationFile('test-publication/foo');
        $this->assertInstanceOf(PublicationPage::class, $file);
        $this->assertEquals('test-publication/foo', $file->getIdentifier());
    }

    public function testParsePublicationFileWithFileExtension()
    {
        $this->createPublicationType();
        $this->createPublication();

        $this->assertEquals(
            PublicationService::parsePublicationFile('test-publication/foo'),
            PublicationService::parsePublicationFile('test-publication/foo.md')
        );
    }

    public function testParsePublicationFileWithNonExistentFile()
    {
        $this->createPublicationType();

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage('File [test-publication/foo.md] not found.');

        PublicationService::parsePublicationFile('test-publication/foo');
    }

    public function testPublicationTypeExists()
    {
        $this->createPublicationType();

        $this->assertTrue(PublicationService::publicationTypeExists('test-publication'));
        $this->assertFalse(PublicationService::publicationTypeExists('foo'));
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
        $this->assertSame($tags, PublicationService::getAllTags()->toArray());
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

        $this->assertSame(['bar', 'baz'], PublicationService::getValuesForTagName('foo')->toArray());
    }

    protected function createPublicationType(): void
    {
        copy(
            Hyde::path('tests/fixtures/test-publication-schema.json'),
            Hyde::path('test-publication/schema.json')
        );
    }

    protected function createPublication(): void
    {
        file_put_contents(
            Hyde::path('test-publication/foo.md'),
            "---\n__canonical: canonical\n__createdAt: 2022-11-16 11:32:52\nfoo: bar\n---\n\nHello World!\n"
        );
    }
}
