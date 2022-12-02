<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Pages\MarkdownPage;
use Hyde\Support\Facades\Render;
use Hyde\Testing\TestCase;
use Illuminate\Support\Facades\View;

/**
 * @covers \Hyde\Support\Models\Render
 * @covers \Hyde\Support\Facades\Render
 */
class RenderHelperTest extends TestCase
{
    public function testSetAndGetPage()
    {
        $this->assertNull(Render::getPage());

        Render::setPage($page = new MarkdownPage());
        $this->assertSame($page, Render::getPage());
    }

    public function testGetCurrentRoute()
    {
        $this->assertNull(Render::getCurrentRoute());

        Render::setPage($page = new MarkdownPage());
        $this->assertEquals($page->getRoute(), Render::getCurrentRoute());
    }

    public function testGetCurrentPage()
    {
        $this->assertNull(Render::getCurrentPage());

        Render::setPage($page = new MarkdownPage());
        $this->assertSame($page->getRouteKey(), Render::getCurrentPage());
    }

    public function testShareToView()
    {
        $this->assertNull(View::shared('page'));
        $this->assertNull(View::shared('currentRoute'));
        $this->assertNull(View::shared('currentPage'));

        Render::setPage($page = new MarkdownPage());
        Render::shareToView();

        $this->assertSame($page, View::shared('page'));
        $this->assertEquals($page->getRoute(), View::shared('currentRoute'));
        $this->assertSame($page->getRouteKey(), View::shared('currentPage'));
    }

    public function testClearData()
    {
        Render::setPage(new MarkdownPage());
        $this->assertNotNull(Render::getPage());

        Render::clearData();
        $this->assertNull(Render::getPage());
    }
}
