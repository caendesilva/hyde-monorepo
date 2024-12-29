<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Hyde;
use Hyde\Pages\BladePage;
use Hyde\Testing\TestCase;
use Illuminate\Support\Arr;
use AllowDynamicProperties;
use Hyde\Foundation\Facades\Routes;
use Hyde\Testing\MocksKernelFeatures;
use Illuminate\Support\Facades\Blade;
use Hyde\Framework\Features\Navigation\NavigationMenu;
use Hyde\Framework\Features\Navigation\NavigationItem;
use Hyde\Framework\Features\Navigation\NavigationGroup;
use Hyde\Framework\Features\Navigation\MainNavigationMenu;
use Hyde\Framework\Features\Navigation\DocumentationSidebar;

/**
 * High level tests for the Navigation API to go along with the code-driven documentation.
 *
 * @see \Hyde\Framework\Features\Navigation\
 *
 * @covers \Hyde\Framework\Features\Navigation\NavigationMenu
 * @covers \Hyde\Framework\Features\Navigation\NavigationItem
 * @covers \Hyde\Framework\Features\Navigation\NavigationGroup
 * @covers \Hyde\Framework\Features\Navigation\MainNavigationMenu
 * @covers \Hyde\Framework\Features\Navigation\DocumentationSidebar
 */
#[AllowDynamicProperties]
class NavigationAPITest extends TestCase
{
    use MocksKernelFeatures;

    public function testServiceContainerMenus()
    {
        // The NavigationServiceProvider binds the main and sidebar navigation menus into the service container.

        Hyde::boot();

        // You can access the menus from the service container.

        $this->assertInstanceOf(MainNavigationMenu::class, app('navigation.main'));
        $this->assertInstanceOf(DocumentationSidebar::class, app('navigation.sidebar'));

        // You can also use the facade to access the menus.

        $this->assertSame(app('navigation.main'), MainNavigationMenu::get());
        $this->assertSame(app('navigation.sidebar'), DocumentationSidebar::get());
    }

    public function testNavigationItems()
    {
        // The NavigationItem class is an abstraction for a navigation menu item containing useful information like the destination, label, and priority.

        // ## Creating Navigation Items

        // There are two syntaxes for creating NavigationItem instances, you can use a standard constructor or the static create method.
        // Both options provide the exact same signature and functionality, so it's just a matter of preference which one you use.

        // The constructors take three parameters: the destination, the label, and the optional priority.
        // The destination can be a Route instance, a route key string, or an external URL.

        // Using a Route instance will automatically fill in the label and priority from the route's connected page.
        $item = new NavigationItem(Routes::get('index'));
        $this->assertData(['destination' => 'index.html', 'label' => 'Home', 'priority' => 0], $item);

        // Using a route key provides the same functionality as using a Route instance.
        // Make sure the route exists otherwise it will be treated as a link.
        $item = new NavigationItem('index');
        $this->assertEquals(new NavigationItem(Routes::get('index')), $item);

        // Setting the label and/or priorities will override the page's data.
        $item = new NavigationItem(Routes::get('index'), 'Custom Label', 10);
        $this->assertData(['destination' => 'index.html', 'label' => 'Custom Label', 'priority' => 10], $item);

        // You can also pass an external URL as the destination.
        $item = new NavigationItem('https://example.com', 'External Link', 10);
        $this->assertData(['destination' => 'https://example.com', 'label' => 'External Link', 'priority' => 10], $item);

        // If you do not set a label for links, the label will default to the URL.
        // And if you do not set a priority, it will default to 500.
        $item = new NavigationItem('https://example.com');
        $this->assertData(['destination' => 'https://example.com', 'label' => 'https://example.com', 'priority' => 500], $item);

        // ## Navigation Item Methods

        // There are a few helper methods available on NavigationItem instances.
        $item = NavigationItem::create('index'); // Documentation ignores this line.

        // Get the label of the item.
        $this->assertSame('Home', $item->getLabel());

        // Get the link of the item.
        $this->assertSame('index.html', $item->getLink());

        // You can also get the link by casting the item to a string.
        $this->assertSame('index.html', (string) $item);

        // Get the priority of the item.
        $this->assertSame(0, $item->getPriority());

        // Get the underlying Page instance of the item, if it exists. If the item is not a routed page (like direct URLs), this will return null.
        $this->assertInstanceOf(BladePage::class, $item->getPage());

        // Check if the item is active (the current page being rendered).
        $this->assertFalse($item->isActive());
    }

    public function testNavigationMenus()
    {
        $this->withPages(['index', 'about', 'contact']);

        // Navigation menus are created with an array of NavigationItem instances.
        $menu = new NavigationMenu([
            new NavigationItem(Routes::get('index'), 'Home'),
            new NavigationItem(Routes::get('about'), 'About'),
            new NavigationItem(Routes::get('contact'), 'Contact'),
        ]);

        $this->assertSame([
            'Home' => 'index.html',
            'About' => 'about.html',
            'Contact' => 'contact.html',
        ], $this->toArray($menu));

        // You can get the items in the menu, which are automatically sorted by their priority.
        foreach ($menu->getItems() as $item) {
            $this->assertInstanceOf(NavigationItem::class, $item);

            $this->assertIsString($item->getLabel());
            $this->assertIsString($item->getLink());
        }
    }

    public function testNavigationMenusWithGroups()
    {
        $this->withPages(['index', 'about', 'contact']);

        // You can also add navigation groups to the menu, which can contain more items.
        $menu = new NavigationMenu([
            new NavigationItem(Routes::get('index'), 'Home'),
            new NavigationGroup('About', [
                new NavigationItem(Routes::get('about'), 'About Us'),
                new NavigationItem(Routes::get('contact'), 'Contact Us'),
            ]),
        ]);

        $this->assertSame([
            'Home' => 'index.html',
            'About' => [
                'items' => [
                    'About Us' => 'about.html',
                    'Contact Us' => 'contact.html',
                ],
            ],
        ], $this->toArray($menu));
    }

    public function testItemSorting()
    {
        // Test items without priorities are returned in the order they were added.

        $menu = new NavigationMenu([
            new NavigationItem('foo', 'foo'),
            new NavigationItem('bar', 'bar'),
            new NavigationItem('baz', 'baz'),
        ]);

        $this->assertSame([
            'foo' => 'foo',
            'bar' => 'bar',
            'baz' => 'baz',
        ], $this->toArray($menu));

        // Test items with priorities are sorted by priority.

        $menu = new NavigationMenu([
            new NavigationItem('foo', 'foo', 10),
            new NavigationItem('baz', 'baz', 15),
            new NavigationItem('bar', 'bar', 5),
        ]);

        $this->assertSame([
            'bar' => 'bar',
            'foo' => 'foo',
            'baz' => 'baz',
        ], $this->toArray($menu));
    }

    public function testSocialMediaFooterLinkExample()
    {
        // To create our menu, we start by constructing a new NavigationMenu instance
        $menu = new NavigationMenu();

        // We can then add our social media links to the menu.
        // We do this by adding NavigationItem instances to the menu.
        $menu->add([
            // The first parameter is the URL, and the second is the label.
            NavigationItem::create('https://twitter.com/hydephp', 'Twitter'),
            NavigationItem::create('https://github.com/hydephp', 'GitHub'),
            NavigationItem::create('https://hydephp.com', 'Website'),
        ]);

        $rendered = Blade::render(/** @lang Blade */ <<<'BLADE'
        {{-- We can now iterate over the menu items to render them in our footer. --}}
        <footer>
            <ul>
                @foreach ($menu->getItems() as $item)
                    <li><a href="{{ $item->getLink() }}">{{ $item->getLabel() }}</a></li>
                @endforeach
            </ul>
        </footer>
        BLADE, ['menu' => $menu]);

        $this->assertSameIgnoringIndentation(<<<'HTML'
        <footer>
            <ul>
                <li><a href="https://twitter.com/hydephp">Twitter</a></li>
                <li><a href="https://github.com/hydephp">GitHub</a></li>
                <li><a href="https://hydephp.com">Website</a></li>
            </ul>
        </footer>
        HTML, $rendered);
    }

    protected function toArray(NavigationMenu $menu): array
    {
        return $menu->getItems()->mapWithKeys(function (NavigationItem|NavigationGroup $item): array {
            if ($item instanceof NavigationGroup) {
                return [
                    $item->getLabel() => [
                        'items' => Arr::mapWithKeys($item->getItems()->all(), function (NavigationItem $item): array {
                            return [$item->getLabel() => $item->getLink()];
                        }),
                    ],
                ];
            }

            return [$item->getLabel() => $item->getLink()];
        })->all();
    }

    protected function assertSameIgnoringIndentation(string $expected, string $actual): void
    {
        $this->assertSame(
            $this->removeIndentation(trim($expected)),
            $this->removeIndentation(trim($actual))
        );
    }

    protected function removeIndentation(string $actual): string
    {
        return implode("\n", array_map('trim', explode("\n", $actual)));
    }

    protected function assertData(array $expected, NavigationItem $item): void
    {
        $actual = [
            'destination' => $item->getLink(),
            'label' => $item->getLabel(),
            'priority' => $item->getPriority(),
        ];

        $this->assertSame($expected, $actual);
    }
}
