<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Testing\UnitTestCase;
use Illuminate\Support\Collection;
use Hyde\Support\Models\ExternalRoute;
use Hyde\Framework\Features\Navigation\NavItem;
use Hyde\Framework\Features\Navigation\NavigationMenu;

/**
 * @covers \Hyde\Framework\Features\Navigation\NavigationMenu
 *
 * @see \Hyde\Framework\Testing\Feature\NavigationMenuTest
 */
class NavigationMenuUnitTest extends UnitTestCase
{
    public function testCanConstruct()
    {
        $this->assertInstanceOf(NavigationMenu::class, new NavigationMenu());
    }

    public function testCanConstructWithItemsArray()
    {
        $this->assertInstanceOf(NavigationMenu::class, new NavigationMenu($this->getItems()));
    }

    public function testCanConstructWithItemsArrayable()
    {
        $this->assertInstanceOf(NavigationMenu::class, new NavigationMenu(collect($this->getItems())));
    }

    public function testGetItemsReturnsCollection()
    {
        $this->assertInstanceOf(Collection::class, (new NavigationMenu())->getItems());
    }

    public function testGetItemsReturnsCollectionWhenSuppliedArray()
    {
        $this->assertInstanceOf(Collection::class, (new NavigationMenu($this->getItems()))->getItems());
    }

    public function testGetItemsReturnsCollectionWhenSuppliedArrayable()
    {
        $this->assertInstanceOf(Collection::class, (new NavigationMenu(collect($this->getItems())))->getItems());
    }

    public function testGetItemsReturnsItems()
    {
        $items = $this->getItems();

        $this->assertSame($items, (new NavigationMenu($items))->getItems()->all());
    }

    public function testGetItemsReturnsItemsWhenSuppliedArrayable()
    {
        $items = $this->getItems();

        $this->assertSame($items, (new NavigationMenu(collect($items)))->getItems()->all());
    }

    public function testGetItemsReturnsEmptyArrayWhenNoItems()
    {
        $this->assertSame([], (new NavigationMenu())->getItems()->all());
    }

    public function testCanAddItems()
    {
        $menu = new NavigationMenu();
        $item = new NavItem(new ExternalRoute('/'), 'Home');

        $menu->add($item);

        $this->assertCount(1, $menu->getItems());
        $this->assertSame($item, $menu->getItems()->first());
    }

    public function testItemsAreInTheOrderTheyWereAddedWhenThereAreNoCustomPriorities()
    {
        $menu = new NavigationMenu();
        $item1 = new NavItem(new ExternalRoute('/'), 'Home');
        $item2 = new NavItem(new ExternalRoute('/about'), 'About');
        $item3 = new NavItem(new ExternalRoute('/contact'), 'Contact');

        $menu->add($item1);
        $menu->add($item2);
        $menu->add($item3);

        $this->assertSame([$item1, $item2, $item3], $menu->getItems()->all());
    }

    public function testItemsAreSortedByPriority()
    {
        $menu = new NavigationMenu();
        $item1 = new NavItem(new ExternalRoute('/'), 'Home', 100);
        $item2 = new NavItem(new ExternalRoute('/about'), 'About', 200);
        $item3 = new NavItem(new ExternalRoute('/contact'), 'Contact', 300);

        $menu->add($item3);
        $menu->add($item1);
        $menu->add($item2);

        $this->assertSame([$item1, $item2, $item3], $menu->getItems()->all());
    }

    protected function getItems(): array
    {
        return [
            new NavItem(new ExternalRoute('/'), 'Home'),
            new NavItem(new ExternalRoute('/about'), 'About'),
            new NavItem(new ExternalRoute('/contact'), 'Contact'),
        ];
    }
}
