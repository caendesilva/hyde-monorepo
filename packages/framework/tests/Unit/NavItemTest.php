<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Framework\Features\Navigation\NavItem;
use Hyde\Pages\InMemoryPage;
use Hyde\Pages\MarkdownPage;
use Hyde\Support\Facades\Render;
use Hyde\Support\Models\Route;
use Hyde\Testing\UnitTestCase;
use Mockery;

/**
 * This unit test covers the basics of the NavItem class.
 * For the full feature test, see the NavigationMenuTest class.
 *
 * @covers \Hyde\Framework\Features\Navigation\NavItem
 *
 * @see \Hyde\Framework\Testing\Unit\NavItemIsCurrentHelperTest
 */
class NavItemTest extends UnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::needsKernel();
        self::mockConfig();
    }

    protected function setUp(): void
    {
        Render::swap(new \Hyde\Support\Models\Render());
    }

    public function test__construct()
    {
        $route = new Route(new MarkdownPage());
        $item = new NavItem($route, 'Test', 500);

        $this->assertSame($route->getLink(), $item->destination);
    }

    public function testFromRoute()
    {
        $route = new Route(new MarkdownPage());
        $item = NavItem::fromRoute($route);

        $this->assertSame($route->getLink(), $item->destination);
    }

    public function test__toString()
    {
        Render::shouldReceive('getCurrentPage')->once()->andReturn('index');

        $this->assertSame('index.html', (string) NavItem::fromRoute(\Hyde\Facades\Route::get('index')));
    }

    public function testToLink()
    {
        $item = NavItem::toLink('foo', 'bar');

        $this->assertSame('foo', $item->destination);
        $this->assertSame('bar', $item->label);
        $this->assertSame(500, $item->priority);
    }

    public function testToLinkWithCustomPriority()
    {
        $this->assertSame(100, NavItem::toLink('foo', 'bar', 100)->priority);
    }

    public function testToRoute()
    {
        $route = \Hyde\Facades\Route::get('index');
        $item = NavItem::toRoute($route, 'foo');

        $this->assertSame($route->getLink(), $item->destination);
        $this->assertSame('foo', $item->label);
        $this->assertSame(500, $item->priority);
    }

    public function testToRouteWithCustomPriority()
    {
        $this->assertSame(100, NavItem::toRoute(\Hyde\Facades\Route::get('index'), 'foo', 100)->priority);
    }

    public function testIsCurrent()
    {
        Render::swap(Mockery::mock(\Hyde\Support\Models\Render::class, [
            'getCurrentRoute' => (new Route(new InMemoryPage('foo'))),
            'getCurrentPage' => (new Route(new InMemoryPage('foo')))->getRouteKey(),
        ]));
        $this->assertTrue(NavItem::fromRoute(new Route(new InMemoryPage('foo')))->isCurrent());
        $this->assertFalse(NavItem::fromRoute(new Route(new InMemoryPage('bar')))->isCurrent());
    }

    public function testGetGroup()
    {
        $route = new Route(new MarkdownPage());
        $item = new NavItem($route, 'Test', 500);

        $this->assertNull($item->getGroup());
    }

    public function testGetGroupWithGroup()
    {
        $route = new Route(new MarkdownPage());
        $item = new NavItem($route, 'Test', 500, 'foo');

        $this->assertSame('foo', $item->getGroup());
    }

    public function testGetGroupFromRouteWithGroup()
    {
        $route = new Route(new MarkdownPage(matter: ['navigation.group' => 'foo']));
        $item = NavItem::fromRoute($route);

        $this->assertSame('foo', $item->getGroup());
    }

    public function testGetGroupToRouteWithGroup()
    {
        $route = new Route(new MarkdownPage(matter: ['navigation.group' => 'foo']));
        $item = NavItem::toRoute($route, 'foo');

        $this->assertSame('foo', $item->getGroup());
    }
}
