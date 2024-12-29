<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Foundation\Facades\Routes;
use Hyde\Framework\Features\Navigation\NavigationItem;
use Hyde\Pages\InMemoryPage;
use Hyde\Support\Facades\Render;
use Hyde\Support\Models\RenderData;
use Hyde\Support\Models\Route;
use Hyde\Testing\UnitTestCase;
use Mockery;

/**
 * @covers \Hyde\Framework\Features\Navigation\NavigationItem
 *
 * @see \Hyde\Framework\Testing\Unit\NavigationItemTest
 */
class NavigationItemIsActiveHelperTest extends UnitTestCase
{
    protected static bool $needsKernel = true;
    protected static bool $needsConfig = true;

    public function testIsCurrent()
    {
        $this->mockRenderData($this->makeRoute('foo'));
        $this->assertFalse(NavigationItem::create($this->makeRoute('bar'))->isActive());
    }

    public function testIsCurrentWhenCurrent()
    {
        $this->mockRenderData($this->makeRoute('foo'));
        $this->assertTrue(NavigationItem::create($this->makeRoute('foo'))->isActive());
    }

    public function testIsCurrentUsingCurrentRoute()
    {
        $this->mockRenderData($this->makeRoute('index'));
        $this->assertTrue(NavigationItem::create(Routes::get('index'))->isActive());
    }

    public function testIsCurrentUsingCurrentLink()
    {
        $this->mockRenderData($this->makeRoute('index'));
        $this->assertTrue(NavigationItem::create('index.html', 'Home')->isActive());
    }

    public function testIsCurrentWhenNotCurrent()
    {
        $this->mockRenderData($this->makeRoute('foo'));
        $this->assertFalse(NavigationItem::create($this->makeRoute('bar'))->isActive());
    }

    public function testIsCurrentUsingNotCurrentRoute()
    {
        $this->mockRenderData($this->makeRoute('foo'));
        $this->assertFalse(NavigationItem::create(Routes::get('index'))->isActive());
    }

    public function testIsCurrentUsingNotCurrentLink()
    {
        $this->mockRenderData($this->makeRoute('foo'));
        $this->assertFalse(NavigationItem::create('index.html', 'Home')->isActive());
    }

    public function testIsCurrentWithNestedCurrentPage()
    {
        $this->mockRenderData($this->makeRoute('foo/bar'));
        $this->assertFalse(NavigationItem::create($this->makeRoute('bar'))->isActive());
    }

    public function testIsCurrentWhenCurrentWithNestedCurrentPage()
    {
        $this->mockRenderData($this->makeRoute('foo/bar'));
        $this->assertTrue(NavigationItem::create($this->makeRoute('foo/bar'))->isActive());
    }

    public function testIsCurrentWithNestedCurrentPageWhenNested()
    {
        $this->mockRenderData($this->makeRoute('foo/bar'));
        $this->assertTrue(NavigationItem::create($this->makeRoute('foo/bar'))->isActive());
    }

    public function testIsCurrentWhenCurrentWithNestedCurrentPageWhenNested()
    {
        $this->mockRenderData($this->makeRoute('foo/bar'));
        $this->assertFalse(NavigationItem::create($this->makeRoute('foo/baz'))->isActive());
    }

    public function testIsCurrentWithNestedCurrentPageWhenVeryNested()
    {
        $this->mockRenderData($this->makeRoute('foo/bar/baz'));
        $this->assertTrue(NavigationItem::create($this->makeRoute('foo/bar/baz'))->isActive());
    }

    public function testIsCurrentWhenCurrentWithNestedCurrentPageWhenVeryNested()
    {
        $this->mockRenderData($this->makeRoute('foo/bar/baz'));
        $this->assertFalse(NavigationItem::create($this->makeRoute('foo/baz/bar'))->isActive());
    }

    public function testIsCurrentWithNestedCurrentPageWhenVeryDifferingNested()
    {
        $this->mockRenderData($this->makeRoute('foo'));
        $this->assertFalse(NavigationItem::create($this->makeRoute('foo/bar/baz'))->isActive());
    }

    public function testIsCurrentWithNestedCurrentPageWhenVeryDifferingNestedInverse()
    {
        $this->mockRenderData($this->makeRoute('foo/bar/baz'));
        $this->assertFalse(NavigationItem::create($this->makeRoute('foo'))->isActive());
    }

    public function testIsCurrentUsingCurrentLinkWithNestedCurrentPage()
    {
        $this->mockRenderData($this->makeRoute('foo/bar'));
        $this->assertFalse(NavigationItem::create('foo/bar.html', 'foo')->isActive());
    }

    public function testIsCurrentUsingNotCurrentLinkWithNestedCurrentPage()
    {
        $this->mockRenderData($this->makeRoute('foo/bar'));
        $this->assertFalse(NavigationItem::create('foo.html', 'foo')->isActive());
    }

    public function testIsCurrentWhenCurrentWithNestedCurrentPageAndSubjectPage()
    {
        $this->mockRenderData($this->makeRoute('foo/bar'));
        $this->assertFalse(NavigationItem::create('foo/bar.html', 'foo')->isActive());
    }

    public function testIsCurrentWhenNotCurrentWithNestedCurrentPageAndSubjectPage()
    {
        $this->mockRenderData($this->makeRoute('foo/bar'));
        $this->assertFalse(NavigationItem::create('foo/baz.html', 'foo')->isActive());
    }

    public function testIsCurrentWithNestedCurrentPageWhenNestedUsingLinkItem()
    {
        $this->mockRenderData($this->makeRoute('foo/bar'));
        $this->assertFalse(NavigationItem::create('foo/bar.html', 'foo')->isActive());
    }

    public function testIsCurrentWhenCurrentWithNestedCurrentPageWhenNestedUsingLinkItem()
    {
        $this->mockRenderData($this->makeRoute('foo/bar'));
        $this->assertFalse(NavigationItem::create('foo/baz.html', 'foo')->isActive());
    }

    public function testIsCurrentWithNestedCurrentPageWhenVeryNestedUsingLinkItem()
    {
        $this->mockRenderData($this->makeRoute('foo/bar/baz'));
        $this->assertFalse(NavigationItem::create('foo/bar/baz.html', 'foo')->isActive());
    }

    public function testIsCurrentWhenCurrentWithNestedCurrentPageWhenVeryNestedUsingLinkItem()
    {
        $this->mockRenderData($this->makeRoute('foo/bar/baz'));
        $this->assertFalse(NavigationItem::create('foo/baz/bar.html', 'foo')->isActive());
    }

    public function testIsCurrentWithNestedCurrentPageWhenVeryDifferingNestedUsingLinkItem()
    {
        $this->mockRenderData($this->makeRoute('foo'));
        $this->assertFalse(NavigationItem::create('foo/bar/baz.html', 'foo')->isActive());
    }

    public function testIsCurrentWithNestedCurrentPageWhenVeryDifferingNestedInverseUsingLinkItem()
    {
        $this->mockRenderData($this->makeRoute('foo/bar/baz'));
        $this->assertFalse(NavigationItem::create('foo.html', 'foo')->isActive());
    }

    public function testIsCurrentWithNestedCurrentPageWhenNestedUsingPrettyLinkItem()
    {
        $this->mockRenderData($this->makeRoute('foo/bar'));
        $this->assertFalse(NavigationItem::create('foo/bar', 'foo')->isActive());
    }

    public function testIsCurrentWhenCurrentWithNestedCurrentPageWhenNestedUsingPrettyLinkItem()
    {
        $this->mockRenderData($this->makeRoute('foo/bar'));
        $this->assertFalse(NavigationItem::create('foo/baz', 'foo')->isActive());
    }

    public function testIsCurrentWithNestedCurrentPageWhenVeryNestedUsingPrettyLinkItem()
    {
        $this->mockRenderData($this->makeRoute('foo/bar/baz'));
        $this->assertFalse(NavigationItem::create('foo/bar/baz', 'foo')->isActive());
    }

    public function testIsCurrentWhenCurrentWithNestedCurrentPageWhenVeryNestedUsingPrettyLinkItem()
    {
        $this->mockRenderData($this->makeRoute('foo/bar/baz'));
        $this->assertFalse(NavigationItem::create('foo/baz/bar', 'foo')->isActive());
    }

    public function testIsCurrentWithNestedCurrentPageWhenVeryDifferingNestedUsingPrettyLinkItem()
    {
        $this->mockRenderData($this->makeRoute('foo'));
        $this->assertFalse(NavigationItem::create('foo/bar/baz', 'foo')->isActive());
    }

    public function testIsCurrentWithNestedCurrentPageWhenVeryDifferingNestedInverseUsingPrettyLinkItem()
    {
        $this->mockRenderData($this->makeRoute('foo/bar/baz'));
        $this->assertFalse(NavigationItem::create('foo', 'foo')->isActive());
    }

    public function testIsCurrentWithAbsoluteLink()
    {
        $this->mockRenderData($this->makeRoute('foo'));
        $this->assertFalse(NavigationItem::create('/foo', 'foo')->isActive());
    }

    public function testIsCurrentWithNestedCurrentPageWhenNestedUsingAbsoluteLinkItem()
    {
        $this->mockRenderData($this->makeRoute('foo/bar'));
        $this->assertFalse(NavigationItem::create('/foo/bar', 'foo')->isActive());
    }

    public function testIsCurrentWhenCurrentWithNestedCurrentPageWhenNestedUsingAbsoluteLinkItem()
    {
        $this->mockRenderData($this->makeRoute('foo/bar/baz'));
        $this->assertFalse(NavigationItem::create('/foo/bar/baz', 'foo')->isActive());
    }

    protected function mockRenderData(Route $route): void
    {
        Render::swap(Mockery::mock(RenderData::class, [
            'getRoute' => $route,
            'getRouteKey' => $route->getRouteKey(),
        ]));
    }

    protected function makeRoute(string $identifier): Route
    {
        return new Route(new InMemoryPage($identifier));
    }
}
