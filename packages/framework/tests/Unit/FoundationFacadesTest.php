<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Foundation\Facades\Files;
use Hyde\Foundation\Facades\PageCollection;
use Hyde\Foundation\Facades\Router;
use Hyde\Foundation\HydeKernel;
use Hyde\Hyde;
use Hyde\Testing\TestCase;

/**
 * @covers \Hyde\Foundation\Facades\Files
 */
class FoundationFacadesTest extends TestCase
{
    public function test_file_collection_facade()
    {
        $this->assertSame(
            HydeKernel::getInstance()->files(),
            Files::getInstance()
        );

        $this->assertEquals(
            Hyde::files()->getSourceFiles(),
            Files::getSourceFiles()
        );
    }

    public function test_page_collection_facade()
    {
        $this->assertSame(
            HydeKernel::getInstance()->pages(),
            PageCollection::getInstance()
        );

        $this->assertEquals(
            Hyde::pages()->getPages(),
            PageCollection::getPages()
        );
    }

    public function test_route_collection_facade()
    {
        $this->assertSame(
            HydeKernel::getInstance()->routes(),
            Router::getInstance()
        );

        $this->assertEquals(
            Hyde::routes()->getRoutes(),
            Router::getRoutes()
        );
    }

    public function test_facade_roots()
    {
        $this->assertSame(Files::getInstance(), Files::getFacadeRoot());
        $this->assertSame(PageCollection::getInstance(), PageCollection::getFacadeRoot());
        $this->assertSame(Router::getInstance(), Router::getFacadeRoot());
    }
}
