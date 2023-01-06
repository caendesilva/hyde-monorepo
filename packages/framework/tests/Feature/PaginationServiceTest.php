<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Framework\Features\Publications\Models\PublicationType;
use Hyde\Framework\Features\Publications\PaginationService;
use Hyde\Testing\TestCase;

use function collect;

/**
 * @covers \Hyde\Framework\Features\Publications\PaginationService
 */
class PaginationServiceTest extends TestCase
{
    public function test_it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(PaginationService::class,
            new PaginationService($this->createMock(PublicationType::class))
        );
    }

    public function testGetPaginatedPageCollection()
    {
        $this->directory('test-publication');
        $this->setupTestPublication();

        $service = new PaginationService(PublicationType::get('test-publication'));
        $collection = $service->getPaginatedPageCollection();
        $this->assertEquals(collect([]), $collection);
    }

    public function testGetPaginatedPageCollectionWithPages()
    {
        $this->directory('test-publication');
        $this->setupTestPublication();

        foreach (range(1, 50) as $i) {
            $this->file("test-publication/$i.md", "title: $i");
        }

        $service = new PaginationService(PublicationType::get('test-publication'));
        $collection = $service->getPaginatedPageCollection();
        $this->assertCount(2, $collection);
        $this->assertCount(25, $collection->first());
        $this->assertCount(25, $collection->last());
    }
}
