<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Testing\UnitTestCase;
use Illuminate\Support\Collection;
use Hyde\Framework\Features\Navigation\NavigationItem;
use Hyde\Framework\Features\Navigation\MainNavigationMenu;

/**
 * @covers \Hyde\Framework\Features\Navigation\NavigationMenu
 * @covers \Hyde\Framework\Features\Navigation\MainNavigationMenu
 *
 * @see \Hyde\Framework\Testing\Feature\NavigationMenuTest
 * @see \Hyde\Framework\Testing\Unit\DocumentationSidebarUnitTest
 */
class NavigationMenuUnitTest extends UnitTestCase
{
    protected static bool $needsKernel = true;
    protected static bool $needsConfig = true;

    // Base menu tests

    public function testCanConstruct()
    {
        $this->assertInstanceOf(MainNavigationMenu::class, new MainNavigationMenu());
    }

    public function testCanConstructWithItemsArray()
    {
        $this->assertInstanceOf(MainNavigationMenu::class, new MainNavigationMenu($this->getItems()));
    }

    public function testCanConstructWithItemsArrayable()
    {
        $this->assertInstanceOf(MainNavigationMenu::class, new MainNavigationMenu(collect($this->getItems())));
    }

    public function testGetItemsReturnsCollection()
    {
        $this->assertInstanceOf(Collection::class, (new MainNavigationMenu())->getItems());
    }

    public function testGetItemsReturnsCollectionWhenSuppliedArray()
    {
        $this->assertInstanceOf(Collection::class, (new MainNavigationMenu($this->getItems()))->getItems());
    }

    public function testGetItemsReturnsCollectionWhenSuppliedArrayable()
    {
        $this->assertInstanceOf(Collection::class, (new MainNavigationMenu(collect($this->getItems())))->getItems());
    }

    public function testGetItemsReturnsItems()
    {
        $items = $this->getItems();

        $this->assertSame($items, (new MainNavigationMenu($items))->getItems()->all());
    }

    public function testGetItemsReturnsItemsWhenSuppliedArrayable()
    {
        $items = $this->getItems();

        $this->assertSame($items, (new MainNavigationMenu(collect($items)))->getItems()->all());
    }

    public function testGetItemsReturnsEmptyArrayWhenNoItems()
    {
        $this->assertSame([], (new MainNavigationMenu())->getItems()->all());
    }

    public function testCanAddItems()
    {
        $menu = new MainNavigationMenu();

        $item = $this->item('/', 'Home');

        $menu->add($item);

        $this->assertCount(1, $menu->getItems());
        $this->assertSame($item, $menu->getItems()->first());
    }

    public function testCanAddMultipleItems()
    {
        $menu = new MainNavigationMenu();

        $item1 = $this->item('/', 'Home');
        $item2 = $this->item('/about', 'About');

        $menu->add($item1);
        $menu->add($item2);

        $this->assertCount(2, $menu->getItems());
        $this->assertSame([$item1, $item2], $menu->getItems()->all());
    }

    public function testCanAddMultipleItemsAtOnce()
    {
        $menu = new MainNavigationMenu();

        $item1 = $this->item('/', 'Home');
        $item2 = $this->item('/about', 'About');

        $menu->add([$item1, $item2]);

        $this->assertCount(2, $menu->getItems());
        $this->assertSame([$item1, $item2], $menu->getItems()->all());
    }

    public function testItemsAreInTheOrderTheyWereAddedWhenThereAreNoCustomPriorities()
    {
        $menu = new MainNavigationMenu();

        $item1 = $this->item('/', 'Home');
        $item2 = $this->item('/about', 'About');
        $item3 = $this->item('/contact', 'Contact');

        $menu->add($item1);
        $menu->add($item2);
        $menu->add($item3);

        $this->assertSame([$item1, $item2, $item3], $menu->getItems()->all());
    }

    public function testItemsAreSortedByPriority()
    {
        $menu = new MainNavigationMenu();

        $item1 = $this->item('/', 'Home', 100);
        $item2 = $this->item('/about', 'About', 200);
        $item3 = $this->item('/contact', 'Contact', 300);

        $menu->add($item3);
        $menu->add($item1);
        $menu->add($item2);

        $this->assertSame([$item1, $item2, $item3], $menu->getItems()->all());
    }

    public function testModifierMethodsAreFluentlyChainable()
    {
        $menu = new MainNavigationMenu();

        $item = $this->item('/', 'Home');

        $this->assertSame($menu, $menu->add($item));
    }

    protected function getItems(): array
    {
        return [
            $this->item('/', 'Home'),
            $this->item('/about', 'About'),
            $this->item('/contact', 'Contact'),
        ];
    }

    protected function item(string $destination, string $label, int $priority = 500): NavigationItem
    {
        return new NavigationItem($destination, $label, $priority);
    }
}
