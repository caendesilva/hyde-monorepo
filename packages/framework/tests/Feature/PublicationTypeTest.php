<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use function array_merge;
use Hyde\Framework\Features\Publications\Models\PublicationType;
use Hyde\Hyde;
use Hyde\Testing\TestCase;
use Illuminate\Support\Facades\File;

/**
 * @covers \Hyde\Framework\Features\Publications\Models\PublicationType
 */
class PublicationTypeTest extends TestCase
{
    public function test_can_construct_new_publication_type()
    {
        $publicationType = new PublicationType(...$this->getTestData());

        foreach ($this->getTestData() as $key => $property) {
            $this->assertEquals($property, $publicationType->$key);
        }
    }

    public function test_class_is_arrayable()
    {
        $publicationType = new PublicationType(...$this->getTestData());
        $this->assertSame($this->getTestData(), $publicationType->toArray());
    }

    public function test_class_is_json_serializable()
    {
        $publicationType = new PublicationType(...$this->getTestData());
        $this->assertSame(json_encode($this->getTestData()), json_encode($publicationType));
    }

    public function test_get_directory()
    {
        $publicationType = new PublicationType(...$this->getTestDataWithPathInformation());
        $this->assertSame('test-publication', $publicationType->getDirectory());
    }

    public function test_get_identifier()
    {
        $publicationType = new PublicationType(...$this->getTestDataWithPathInformation());
        $this->assertSame('test-publication', $publicationType->getIdentifier());
    }

    public function test_get_identifier_with_no_directory()
    {
        $publicationType = new PublicationType(...$this->getTestData());
        $this->assertSame('test', $publicationType->getIdentifier());
    }

    public function test_can_save_to_json_file()
    {
        $publicationType = new PublicationType(...$this->getTestDataWithPathInformation());
        $publicationType->save();

        $this->assertFileExists('test-publication/schema.json');
        $this->assertSame(json_encode($this->getTestData(), 128), file_get_contents(Hyde::path('test-publication/schema.json')));

        File::deleteDirectory(Hyde::path('test-publication'));
    }

    public function test_can_save_to_json_file_using_custom_path()
    {
        $publicationType = new PublicationType(...$this->getTestData());
        $publicationType->save('test-publication/foo.json');

        $this->assertFileExists('test-publication/foo.json');
        $this->assertSame(json_encode($this->getTestData(), 128), file_get_contents(Hyde::path('test-publication/foo.json')));

        File::deleteDirectory(Hyde::path('test-publication'));
    }

    public function test_can_load_from_json_file()
    {
        $publicationType = new PublicationType(...array_merge($this->getTestData(), [
            'directory' => 'tests/fixtures',
        ]));

        $this->assertEquals($publicationType, PublicationType::fromFile(Hyde::path('tests/fixtures/test-publication-schema.json')));
    }

    protected function getTestData(): array
    {
        return [
            'name'           => 'test',
            'canonicalField' => 'canonical',
            'sortField'      => 'sort',
            'sortDirection'  => 'asc',
            'pageSize'       => 10,
            'prevNextLinks'  => true,
            'detailTemplate' => 'detail',
            'listTemplate'   => 'list',
            'fields'         => [
                'foo' => 'bar',
            ],
        ];
    }

    protected function getTestDataWithPathInformation(): array
    {
        return array_merge($this->getTestData(), [
            'directory' => 'test-publication',
        ]);
    }
}
