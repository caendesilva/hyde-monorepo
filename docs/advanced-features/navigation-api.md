---
navigation:
    label: "Navigation API"
---

# Navigation API

>warning This article covers advanced information that is only relevant if you want to create custom navigation menus. Instead, you may want to read the [Navigation](navigation) article for a general overview.

## Abstract

This article describes the Navigation API introduced in HydePHP v2. Both the main navigation menu and the documentation sidebar bundled with HydePHP are built with this API.
However, if you are interested in creating your own custom navigation menus, you can also utilize this API - and this article will show you how.

This article is intended for advanced users, as most users will not need to create custom navigation menus.
For this reason, the documentation is very code-driven due to the technical nature of the API.
We'll also be mixing in some practical examples of Blade and PHP code to illustrate how you can use the API in your own projects.

## Overview

The Navigation API consists of a set of classes within the `Hyde\Framework\Features\Navigation` namespace.

Together, these form an object-oriented way to create and interact with navigation menus and their items.

In short, a navigation menu is an instance of the `NavigationMenu` class. Each menu contains a collection of `NavigationItem` or `NavigationGroup` classes.
The former represents a single item in the menu, while the latter represents a group of items.

### Visualisation

Here is a visual representation of the structure of a navigation menu:

```php
namespace Hyde\Framework\Features\Navigation;

class NavigationMenu {
    protected Collection $items = [
        new NavigationItem(destination: 'index.html', label: 'Home'),
        new NavigationItem(destination: 'posts.html', label: 'Blog'),
        new NavigationGroup(label: 'About', items: [
            new NavigationItem(destination: 'about.html', label: 'About Us'),
            new NavigationItem(destination: 'team.html', label: 'Our Team'),
        ]),
    ];
}
```

### Built-in Menus

HydePHP comes with two built-in menus: the main navigation menu and the documentation sidebar, both represented as child classes of the `NavigationMenu` class.
They are bound into the service container as singletons and can be accessed through dependency injection.

```php
use Hyde\Framework\Features\Navigation\MainNavigationMenu;
use Hyde\Framework\Features\Navigation\DocumentationSidebar;

/** @var \Hyde\Framework\Features\Navigation\MainNavigationMenu $menu */
$menu = app('navigation.main')

/** @var DocumentationSidebar $sidebar */
$sidebar= app('navigation.sidebar')
```

You can also get them through the static `get` helpers on the menu classes themselves.

```php
use Hyde\Framework\Features\Navigation\MainNavigationMenu;
use Hyde\Framework\Features\Navigation\DocumentationSidebar;

$menu = MainNavigationMenu::get();
$sidebar = DocumentationSidebar::get();
```

>info Developer tip: The menus are only generated *after* the Hyde Kernel is booted. If you are getting BindingResolutionExceptions, ensure that you are not trying to access the menus too early in the application lifecycle. (Consider using the `booted` event.)

## Creating Custom Menus

### Introduction Overview

When creating a custom menu, there are two ways to go about it:

1. You can construct a NavigationMenu instance directly and add items to it. This works great for smaller menus that don't need any special logic, for example a footer menu or social media links.
2. You can create a custom class that extends the NavigationMenu class. This is useful for larger menus that require more complex logic, or that you want to reuse in multiple places in your application or in a package.

In both cases, the underlying API is the same, and you can use the helper methods and features provided by the APIs.

In this section, you will first see some high level overviews of how the API can be used, then we'll dive in deeper and take a look at each class and method in detail.

### High Level Example

To illustrate how you can create a custom menu, let's make something useful: A footer menu with social media links.

#### Step 1: Create the Menu

To create our menu, we start by constructing a new NavigationMenu instance.
We can then add our social media links as NavigationItem instances to the menu.

```php
$menu = new NavigationMenu();

$menu->add([
    // The first parameter is the URL, and the second is the label.
    NavigationItem::create('https://twitter.com/hydephp', 'Twitter'),
    NavigationItem::create('https://github.com/hydephp', 'GitHub'),
    NavigationItem::create('https://hydephp.com', 'Website'),
]);
```

#### Step 2: Display the Menu

We can now iterate over the menu items to render them in our footer.

```blade
<footer>
    <ul>
        @foreach ($menu->getItems() as $item)
            <li><a href="{{ $item->getLink() }}">{{ $item->getLabel() }}</a></li>
        @endforeach
    </ul>
</footer>
```

This will result in the following HTML:

```html
<footer>
    <ul>
        <li><a href="https://twitter.com/hydephp">Twitter</a></li>
        <li><a href="https://github.com/hydephp">GitHub</a></li>
        <li><a href="https://hydephp.com">Website</a></li>
    </ul>
</footer>
```

#### Next Steps & Tips

Of course, this is an incredibly simplistic example to illustrate the core concepts.
Where the Navigation API really shines is in more complex scenarios where you want to utilize things like
HydePHP Routes to resolve dynamic relative urls, and to use features like groups, priorities, and active states helpers to check if a menu item is the current page being viewed.

The object-oriented nature of the API also makes this perfect for package developers wanting to create dynamic and reusable navigation menus that even can be further extended and customized by the end user.

Here are some general tips to keep in mind when working with the Navigation API:
- You can use the `add` method to add single items or arrays of items. You can also pass an array of items directly to the menu constructor.
- The navigation menu items is stored in a Laravel Collection, and is type safe to support both `NavigationItem` and `NavigationGroup` instances.
- You can also construct NavigationItem instances directly, but the `create` method is a convenient shorthand, and can fill in data from routes, if you use them.
- It's also possible to set an item's priority as the third parameter, but here we don't need it, as they default to the order they are added.

## Class Reference

Below is a reference of the classes and methods available in the Navigation API.

## NavigationMenu

The `NavigationMenu` class represents a navigation menu. It contains a collection of items, which can be either `NavigationItem` or `NavigationGroup` instances.

### Quick Reference

Here is a quick reference of the methods available on the NavigationMenu class:

```php
use Hyde\Framework\Features\Navigation\NavigationMenu;

// Create a new NavigationMenu instance, optionally providing an array of items.
$menu = new NavigationMenu($items = []);

// Add a single item or an array of items to the menu.
$menu->add(new NavigationItem());
$menu->add([new NavigationItem()]);

// Get all items in the menu as a sorted Collection.
$menu->getItems(): Collection<NavigationItem|NavigationGroup>
```

### Creating Navigation Menus

You can create a new NavigationMenu instance by simply calling the constructor, optionally providing an array of items.

```php
use Hyde\Framework\Features\Navigation\NavigationMenu;

$menu = new NavigationMenu($items = []);
```

Here is how to provide an array or Collection of `NavigationItem` and/or `NavigationGroup` instances directly to the constructor.

```php
use Hyde\Framework\Features\Navigation\NavigationMenu;
use Hyde\Framework\Features\Navigation\NavigationItem;
use Hyde\Framework\Features\Navigation\NavigationGroup;

$menu = new NavigationMenu([
    new NavigationItem('index.html', 'Home'),
    new NavigationItem('posts.html', 'Blog'),
    new NavigationGroup('About', [
        new NavigationItem('about.html', 'About Us'),
        new NavigationItem('team.html', 'Our Team'),
    ]),
]);
```

### Adding Items to the Menu

You can also add items to the menu after it has been created by using the `add` method which can take a single item or an array of items, and can be fluently chained.

```php
$menu = (new NavigationMenu())
    ->add(new NavigationItem('contact.html', 'Contact Us'))
    ->add([
        new NavigationItem('privacy.html', 'Privacy Policy'),
        new NavigationItem('terms.html', 'Terms of Service'),
    ]);
```

### Accessing Items in the Menu

You can access all items in the menu by calling the `getItems` method, which will return a `Collection` of all items in the menu.

```php
$items = $menu->getItems();
```

The items will automatically be sorted by their priority, with lower numbers coming first, defaulting to the order they were added if no priority is set.

## NavigationItem

The `NavigationItem` class is an abstraction for a navigation menu item containing useful information like the destination, label, and priority.

### Quick Reference

Here is a quick reference of the methods available on the `NavigationItem` class:

```php
use Hyde\Framework\Features\Navigation\NavigationItem;

// Create a new NavigationItem instance.
$item = NavigationItem::create($destination, $label, $priority): NavigationItem;
$item = new NavigationItem($destination, $label, $priority); // Same as above.

// Get the link of the item.
$item->getLink(): string;

// Get the label of the item.
$item->getLabel(): string;

// Get the priority of the item.
$item->getPriority(): int;

// Check if the item is active. (Only works when the destination is a route)
$item->isActive(): bool;
```

### Blade Example

Here is an example of how you can put it all together in a Blade template:

```blade
<a href="{{ $item->getLink() }}" @class(['active' => $item->isActive()])>
    {{ $item->getLabel() }}
</a>
```

This will output an anchor tag with the correct link and label, and if the item is active, it will add an `active` class to the tag.

### Creating Navigation Items

There are two syntaxes for creating `NavigationItem` instances, you can use a standard constructor or the static create method.
Both options provide the exact same signature and functionality, so it's just a matter of preference which one you use.

The constructors take three parameters: the destination, the label, and the optional priority.
The destination can be a `Route` instance, a route key string, or an external URL.

```php
use Hyde\Framework\Features\Navigation\NavigationItem;

$item = new NavigationItem($destination, $label, $priority);
$item = NavigationItem::create($destination, $label, $priority);
```

#### Using Routes

Using the HydePHP routing system is the recommended way to create navigation items leading to pages within your project,
as they will automatically have links resolved to the correct URL, and Hyde can check if the items are active.
Additionally, Hyde will use the page data as the label and priority defaults unless you override them.

You can create routed navigation items by providing either a `Route` instance or a route key string as the destination.

```php
// Using a route key string.
new NavigationItem('index');

// Using the Routes facade to get a Route instance.
new NavigationItem(Routes::get('index'));

// Setting the label and/or priorities will override inferred data.
new NavigationItem(Routes::get('index'), 'Custom Label', 25);
```

Using a route key is more concise, but will not provide type safety as it will be treated as a link if the route does not exist,
whereas providing an invalid route key to the `Routes` facade will throw an exception. It's up to you which one you prefer.

#### Using External URLs

You can also create navigation items that link to external URLs by providing a full URL as the destination.

If you do not set a label for links, the label will default to the URL, and if you do not set a priority, it will default to `500`.

```php
// This will lead directly to the link, and use it as the label with a priority of 500.
new NavigationItem('https://example.com');

// You can also set a custom label and priority to override the defaults.
new NavigationItem('https://example.com', 'External Link', 25);
```

While it is discouraged to use external URLs for internal pages, as Hyde won't be able to resolve relative links or check active states,
they are excellent for any time you want an absolute link to an external site or resource.

Note that Hyde will not validate or modify the URL, so you are responsible for ensuring it's correct.

### Accessing the resolved links

The `getLink` method is designed to return a link that can be used in the `href` attribute of an anchor tag.

If the destination is a route, the link will be resolved to the correct URL, using relative paths if possible. It will also respect the pretty URL setting.

```php
$item = new NavigationItem(Routes::get('index'));
$item->getLink(); // Outputs 'index.html'

$item = new NavigationItem('https://example.com');
$item->getLink(); // Outputs 'https://example.com'
```

**Tip:** The item instances automatically turns into the resolved link when cast to a string. Perfect for your Blade templates!

```blade
<a href="{{ $item }}">{{ $item->getLabel() }}</a>
```

### Accessing the label

The `getLabel` method returns the label of the item. This is the text that will be displayed in the navigation menu.

```php
$item = new NavigationItem('index', 'Home');
$item->getLabel(); // Outputs 'Home'
```

### Accessing the priority

The `getPriority` method returns the priority of the item. This is a number that determines the order in which the items are displayed in the menu, where lower numbers come first.

```php
$item = new NavigationItem('index', 'Home', 25);
$item->getPriority(); // Outputs 25
```

### Checking if the item is active

The `isActive` method checks if the item is active (by comparing it to the Hyde page being compiled at the moment). This is useful for highlighting the current page in the navigation menu.

```php
$item = new NavigationItem('index');
$item->isActive(); // Outputs true if the item is the current page, otherwise false.
```

<!--
Generated by Copilot, kinda cool, maybe something to implement?

### Advanced Usage

#### Customizing the Active State

By default, the `isActive` method will check if the item's destination matches the current page being compiled.

However, you can also provide a custom callback to the method to determine if the item is active.

```php
$item = new NavigationItem('index');
$item->isActive(fn($item) => $item->getLink() === 'index.html');
```

This is useful if you want to check for a specific query parameter, or if you want to check if the item is active based on a more complex condition.
-->

## NavigationGroup

The `NavigationGroup` class represents a group of items in a navigation menu. It contains a label, priority, and a collection of navigation items.
This class is often used to create submenus or dropdowns in a navigation menu.

The `NavigationGroup` class extends the `NavigationMenu` class, and thus inherits the same base methods and functionality,
while also having shared methods with the `NavigationItem` class to render the groups in a Blade view.

### Quick Reference

Here is a quick reference of the methods available on the `NavigationGroup` class:

```php
use Hyde\Framework\Features\Navigation\NavigationGroup;

// Create a new NavigationGroup instance.
$group = new NavigationGroup($label, $items = [], $priority = 500);

// Add a single item or an array of items to the group.
$group->add(new NavigationItem());
$group->add([new NavigationItem()]);

// Get all items in the group as a Collection sorted by priority.
$group->getItems(): Collection<NavigationItem|NavigationGroup>

// Get the label of the group.
$group->getLabel(): string;

// Get the priority of the group.
$group->getPriority(): int;

// Get the group key, which is a normalized kebab-case version of the label.
$group->getGroupKey(): string;
```

As the `NavigationGroup` class extends the `NavigationMenu` class, please see the `NavigationMenu` section for detailed information of the methods available.

### Usage Scenarios

HydePHP uses the `NavigationGroup` class to create dropdowns in the main navigation menu and the category groups in the documentation sidebar.

In your own custom menus, you can use this class for the same types of functionality, and you can even nest groups within groups to create complex navigation structures.
