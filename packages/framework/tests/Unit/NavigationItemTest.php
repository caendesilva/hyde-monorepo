<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Foundation\Facades\Routes;
use Hyde\Framework\Features\Navigation\NavigationItem;
use Hyde\Pages\InMemoryPage;
use Hyde\Pages\MarkdownPage;
use Hyde\Support\Facades\Render;
use Hyde\Support\Models\RenderData;
use Hyde\Support\Models\Route;
use Hyde\Testing\UnitTestCase;
use Mockery;
use Hyde\Framework\Features\Navigation\NavigationGroup;

/**
 * This unit test covers the basics of the NavigationItem class.
 * For the full feature test, see the MainNavigationMenuTest class.
 *
 * @covers \Hyde\Framework\Features\Navigation\NavigationItem
 *
 * @see \Hyde\Framework\Testing\Unit\NavigationItemIsActiveHelperTest
 */
class NavigationItemTest extends UnitTestCase
{
    protected static bool $needsKernel = true;
    protected static bool $needsConfig = true;
    protected static bool $needsRender = true;

    public function testConstruct()
    {
        $this->assertInstanceOf(NavigationItem::class, new NavigationItem('foo', 'Test'));
        $this->assertInstanceOf(NavigationItem::class, new NavigationItem(new Route(new MarkdownPage()), 'Test'));
        $this->assertInstanceOf(NavigationItem::class, new NavigationItem(new Route(new MarkdownPage()), 'Test', 500));
    }

    public function testPassingRouteInstanceToConstructorUsesRouteInstance()
    {
        $route = new Route(new MarkdownPage());
        $this->assertEquals($route, (new NavigationItem($route, 'Home'))->getPage()->getRoute());
    }

    public function testPassingRouteKeyToConstructorUsesDestinationAsRoute()
    {
        $item = new NavigationItem('index', 'Home');
        $this->assertSame(Routes::get('index')->getPage(), $item->getPage());
        $this->assertSame('index', $item->getPage()->getRouteKey());
        $this->assertSame('index.html', $item->getLink());
    }

    public function testPassingUrlToConstructorSetsRouteToNull()
    {
        $item = new NavigationItem('https://example.com', 'Home');
        $this->assertNull($item->getPage());
        $this->assertSame('https://example.com', $item->getLink());
    }

    public function testPassingUnknownRouteKeyToConstructorSetsRouteToNull()
    {
        $item = new NavigationItem('foo', 'Home');
        $this->assertNull($item->getPage());
        $this->assertSame('foo', $item->getLink());
    }

    public function testCanGetPage()
    {
        $page = new MarkdownPage();
        $item = new NavigationItem(new Route($page), 'Test', 500);

        $this->assertSame($page, $item->getPage());
    }

    public function testGetPageRoute()
    {
        $route = new Route(new InMemoryPage('foo'));
        $NavigationItem = new NavigationItem($route, 'Page', 500);

        $this->assertEquals($route, $NavigationItem->getPage()->getRoute());
    }

    public function testGetLink()
    {
        $NavigationItem = new NavigationItem(new Route(new InMemoryPage('foo')), 'Page', 500);
        $this->assertSame('foo.html', $NavigationItem->getLink());
    }

    public function testGetLabel()
    {
        $NavigationItem = new NavigationItem(new Route(new InMemoryPage('foo')), 'Page', 500);
        $this->assertSame('Page', $NavigationItem->getLabel());
    }

    public function testGetPriority()
    {
        $NavigationItem = new NavigationItem(new Route(new InMemoryPage('foo')), 'Page', 500);
        $this->assertSame(500, $NavigationItem->getPriority());
    }

    public function testFromRoute()
    {
        $page = new MarkdownPage();
        $route = new Route($page);
        $item = NavigationItem::create($route);

        $this->assertSame($page, $item->getPage());
        $this->assertEquals($route, $item->getPage()->getRoute());
    }

    public function testToString()
    {
        Render::shouldReceive('getRouteKey')->once()->andReturn('index');

        $this->assertSame('index.html', (string) NavigationItem::create(Routes::get('index')));
    }

    public function testCreateWithLink()
    {
        $item = NavigationItem::create('foo', 'bar');

        $this->assertNull($item->getPage());
        $this->assertSame('foo', $item->getLink());
        $this->assertSame('bar', $item->getLabel());
        $this->assertSame(500, $item->getPriority());
    }

    public function testCreateWithLinkWithCustomPriority()
    {
        $this->assertSame(100, NavigationItem::create('foo', 'bar', 100)->getPriority());
    }

    public function testCreate()
    {
        $route = Routes::get('404');
        $item = NavigationItem::create($route, 'foo');

        $this->assertSame($route->getPage(), $item->getPage());
        $this->assertSame('foo', $item->getLabel());
        $this->assertSame(999, $item->getPriority());
        $this->assertEquals($route, $item->getPage()->getRoute());
        $this->assertSame('404.html', $item->getLink());
        $this->assertSame('404.html', (string) $item);
    }

    public function testForIndexRoute()
    {
        $route = Routes::get('index');
        $item = NavigationItem::create($route);

        $this->assertSame($route->getPage(), $item->getPage());
        $this->assertSame('Home', $item->getLabel());
        $this->assertSame(0, $item->getPriority());
        $this->assertEquals($route, $item->getPage()->getRoute());
        $this->assertSame('index.html', $item->getLink());
        $this->assertSame('index.html', (string) $item);
    }

    public function testCreateWithLabels()
    {
        $route = Routes::get('404');
        $item = NavigationItem::create($route, 'foo');
        $this->assertSame('foo', $item->getLabel());

        $route = Routes::get('index');
        $item = NavigationItem::create($route);
        $this->assertSame('Home', $item->getLabel());
    }

    public function testCreateWithRouteKey()
    {
        $this->assertEquals(
            NavigationItem::create(Routes::get('index'), 'foo'),
            NavigationItem::create('index', 'foo')
        );
    }

    public function testCreateWithMissingRouteKey()
    {
        $this->assertNull(NavigationItem::create('foo', 'foo')->getPage());
    }

    public function testCreateWithCustomPriority()
    {
        $this->assertSame(100, NavigationItem::create(Routes::get('index'), 'foo', 100)->getPriority());
    }

    public function testCreateWithNullLabelForRoute()
    {
        $this->assertSame('Home', NavigationItem::create('index')->getLabel());
    }

    public function testCreateWithNullLabel()
    {
        $this->assertSame('foo', NavigationItem::create('foo')->getLabel());

        $links = [
            'www.example.com',
            'https://example.com',
            'https://example.com/',
            'https://example.com/foo',
            'https://example.com/foo/',
            'https://example.com/foo/bar',
            'https://example.com/foo/bar.html',
            'https://example.com/foo/bar.png',
        ];

        foreach ($links as $link) {
            $this->assertSame($link, NavigationItem::create($link)->getLabel());
        }
    }

    public function testConstructWithNullLabel()
    {
        $this->assertSame('foo', (new NavigationItem('foo'))->getLabel());

        $links = [
            'www.example.com',
            'https://example.com',
            'https://example.com/',
            'https://example.com/foo',
            'https://example.com/foo/',
            'https://example.com/foo/bar',
            'https://example.com/foo/bar.html',
            'https://example.com/foo/bar.png',
        ];

        foreach ($links as $link) {
            $this->assertSame($link, (new NavigationItem($link))->getLabel());
        }
    }

    public function testPassingRouteKeyToStaticConstructorUsesRouteInstance()
    {
        $route = Routes::get('index');
        $item = NavigationItem::create('index', 'Home');
        $this->assertNotNull($item->getPage());
        $this->assertSame($route->getPage(), $item->getPage());
    }

    public function testRouteBasedNavigationItemDestinationsAreResolvedRelatively()
    {
        Render::swap(Mockery::mock(RenderData::class, [
            'getRoute' => new Route(new InMemoryPage('foo')),
            'getRouteKey' => 'foo',
        ]));

        $this->assertSame('foo.html', (string) NavigationItem::create(new Route(new InMemoryPage('foo'))));
        $this->assertSame('foo/bar.html', (string) NavigationItem::create(new Route(new InMemoryPage('foo/bar'))));

        Render::swap(Mockery::mock(RenderData::class, [
            'getRoute' => new Route(new InMemoryPage('foo/bar')),
            'getRouteKey' => 'foo/bar',
        ]));

        $this->assertSame('../foo.html', (string) NavigationItem::create(new Route(new InMemoryPage('foo'))));
        $this->assertSame('../foo/bar.html', (string) NavigationItem::create(new Route(new InMemoryPage('foo/bar'))));

        Render::swap(Mockery::mock(RenderData::class, [
            'getRoute' => new Route(new InMemoryPage('foo/bar/baz')),
            'getRouteKey' => 'foo/bar/baz',
        ]));

        $this->assertSame('../../foo.html', (string) NavigationItem::create(new Route(new InMemoryPage('foo'))));
        $this->assertSame('../../foo/bar.html', (string) NavigationItem::create(new Route(new InMemoryPage('foo/bar'))));
    }

    public function testDropdownFacade()
    {
        $item = NavigationGroup::create('foo');

        $this->assertSame('foo', $item->getLabel());
        $this->assertSame([], $item->getItems()->all());
        $this->assertSame(999, $item->getPriority());
    }

    public function testDropdownFacadeWithChildren()
    {
        $children = [
            new NavigationItem(new Route(new MarkdownPage()), 'bar'),
        ];

        $item = NavigationGroup::create('foo', $children);
        $this->assertSame($children, $item->getItems()->all());
        $this->assertSame(999, $item->getPriority());
    }

    public function testDropdownFacadeWithCustomPriority()
    {
        $item = NavigationGroup::create('foo', [], 500);

        $this->assertSame(500, $item->getPriority());
    }

    public function testIsCurrent()
    {
        Render::swap(Mockery::mock(RenderData::class, [
            'getRoute' => new Route(new InMemoryPage('foo')),
            'getRouteKey' => 'foo',
        ]));
        $this->assertTrue(NavigationItem::create(new Route(new InMemoryPage('foo')))->isActive());
        $this->assertFalse(NavigationItem::create(new Route(new InMemoryPage('bar')))->isActive());
    }

    public function testIsCurrentWithLink()
    {
        Render::swap(Mockery::mock(RenderData::class, [
            'getRoute' => new Route(new InMemoryPage('foo')),
            'getRouteKey' => 'foo',
        ]));
        $this->assertFalse(NavigationItem::create('foo', 'bar')->isActive());
        $this->assertFalse(NavigationItem::create('https://example.com', 'bar')->isActive());
    }

    public function testIsCurrentIsNullSafe()
    {
        $this->assertFalse(NavigationItem::create('foo', 'bar')->isActive());
    }

    public function testConstructWithAttributes()
    {
        $item = new NavigationItem('foo', 'Test', 500, ['class' => 'active']);
        $this->assertSame(['class' => 'active'], $item->getExtraAttributes());
    }

    public function testCreateWithAttributes()
    {
        $item = NavigationItem::create('foo', 'Test', 500, ['class' => 'active']);
        $this->assertSame(['class' => 'active'], $item->getExtraAttributes());
    }
}
