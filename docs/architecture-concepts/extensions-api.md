---
navigation.priority: 1000
---

# The Extensions API

## Introduction

The Extensions API is a powerful interface designed for package developers who want to extend the functionality of HydePHP.

Using the API, you can hook directly into the HydePHP Kernel and extend sites with custom page types and new features.

This documentation page functions heavily through examples, so it's recommended that the sections are read in order.

### Prerequisites

Before creating your extension, it will certainly be helpful if you first become familiar with
the basic internal architecture of HydePHP, as well as how the auto-discovery system works,
so you can understand how your code works with the internals.

- [Core concepts overview](core-concepts)
- [Architecture concepts](architecture-concepts)
- [Autodiscovery](autodiscovery)

### The why and how of the Extensions API

HydePHP being a static site generator, the Extensions API is centred around [Page Models](page-models),
which you are hopefully already familiar with, otherwise you should read up on them first.

What the Extensions API does is to allow you to create custom page types, and tell HydePHP how to discover them.
This may sound like a small thing, but it's actually incredibly powerful as the page models are the foundation
of HydePHP's functionality. They tell the system how to discover pages, how to render them,
and how they interact with the site.

Any other functionality you want to add to HydePHP, such as new commands or configuration options,
can be added the same way as you would in Laravel, and are thus not part of our API.
See the [Laravel package development guide](https://laravel.com/docs/10.x/packages) for more.

## Creating Your Extension Class

The entry-point for your extension is your Extensions class. Within this, you can register the custom page classes.
If needed, you can also register discovery handlers which can run custom logic at various parts of the boot process.

In this article we will create an extension that registers a new type of page, a `JsonPageExtension`.

The first step is to create a class that extends the `HydeExtension` class:

```php
use Hyde\Foundation\Concerns\HydeExtension;

class JsonPageExtension extends HydeExtension {
    //
}
```

In here, we will register our extension class name in the `getPageClasses` method:

```php
class JsonPageExtension extends HydeExtension {
    public static function getPageClasses(): array {
        return [
            JsonPage::class,
        ];
    }
}
```

Hyde will then use the information from the `JsonPage` class to automatically discover the pages when booting the Kernel.
For example, if you specify the file extension and source directory, that is all Hyde needs to know to discover the pages.

If our pages need more complex discovery logic, we can create custom handlers. so let's take a quick look at that next.

### Discovery handlers

The discovery handlers let you run code at various points of the booting process. This is usually only needed if your
page models cannot provide the information required for Hyde run the standard auto-discovery, and thus need custom logic.

Usually in these cases, you would only need to add files to the Kernel `FileCollection`,
though the `HydeExtension` class offers the following three discovery handlers, in case you need them:

```php
/** Runs during file discovery */
public function discoverFiles(FileCollection $collection): void;

/** Runs during page discovery */
public function discoverPages(PageCollection $collection): void;

/** Runs during route discovery */
public function discoverRoutes(RouteCollection $collection): void;
```

Any of these can be implemented in your extension class, and they will be called during the discovery. As you can see,
the instance of the discovery collection is injected into the method for you to interact with.

### Booting and Booted Callbacks

In addition to the discovery handlers, you can also define `booting` and `booted` callbacks in your extension class. These methods allow you to run custom logic before and after the kernel boots, respectively. Both methods receive the `HydeKernel` instance as a parameter, allowing you to interact with the kernel directly.

```php
use Hyde\Foundation\HydeKernel;

class JsonPageExtension extends HydeExtension {
    public function booting(HydeKernel $kernel): void {
        // This runs before the kernel boots and before running auto-discovery
    }

    public function booted(HydeKernel $kernel): void {
        // This runs after the kernel boots and after running auto-discovery
    }
}
```

The `booting` callback can be used to set up the system with information needed to discover pages, while the `booted` callback can can be used to perform operations requiring knowledge of discovered data, even modifying them if needed.

These callbacks provide powerful hooks into the Hyde system, allowing your extensions to integrate deeply and modify Hyde's behavior as needed. 

#### Discovery handler example

Let's go crazy and implement a discovery handler to collect `JsonPage` files from an external API! We will do this
by implementing the `discoverPages` method in our extension class, and from there inject pages retrieved from our API.

```php
class JsonPageExtension extends HydeExtension {
    public function discoverPages(PageCollection $collection): void {
        $pages = Http::get('https://example.com/my-api')->collect();

        $pages->each(function (array $page) use ($collection): void {
            $collection->addPage(JsonPage::fromArray($page));
        });
    }
}
```

Since the discovery steps are handled sequentially, the added pages will automatically be discovered as routes without
us having to implement that handler method. As we inject the page objects directly, we bypass the need for the `FileCollection`.

## Registering Your Extension

Now that we have our extension class, we need to register it with HydePHP.

It's important that your class is registered before the HydeKernel boots. Therefore, an excellent place for this is the
`register` method of your extensions service provider,  where you call the `registerExtension` method of the `HydeKernel`
singleton instance, which you can access via the `Hyde\Hyde` facade, or via the service container.

```php
use Hyde\Hyde;
use Hyde\Foundation\HydeKernel;
use Illuminate\Support\ServiceProvider;

class JsonPageExtensionServiceProvider extends ServiceProvider {
    public function register(): void {
        // Via the service container:
        $this->app->make(HydeKernel::class)->registerExtension(JsonPageExtension::class);

        // Or via the facade:
        Hyde::registerExtension(JsonPageExtension::class);
    }
}
```

### Packaging your extension

To make your extension available to other HydePHP users, you can make it into a [Composer](https://getcomposer.org/) package,
and publish it to [Packagist](https://packagist.org/) for others to install.

If you register your service provider in your package's `composer.json` file, your extension automatically be enabled when
the package is installed in a HydePHP project!

```json
{
  "extra": {
    "laravel": {
      "providers": [
        "My\\Namespace\\JsonPageExtensionServiceProvider"
      ]
    }
  }
}
```

### Telling the world about your extension

Next up, why not send us a Tweet at [@HydeFramework](https://twitter.com/HydeFramework) and tell us about your extension,
so we can feature it?
