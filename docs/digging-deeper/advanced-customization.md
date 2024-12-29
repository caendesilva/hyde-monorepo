---
navigation:
    label: "Advanced Customization"
    priority: 30
---

# Advanced Customization

## Introduction

This page covers advanced usage and is intended for developers who know what they are doing. Documentation here will be
mainly example driven, as it is assumed you have somewhat of an understanding of what you are doing already.

### Prerequisites for customizing directories

>info Before customizing source and output directories it may be helpful to know how these settings are stored internally.

**The following is a summary from the [Page Models](page-models) documentation:**

Each page type is represented by a page model class. Each of those classes have static properties that store the source and output directories.
These properties are set when the [`HydeServiceProvider`](https://github.com/hydephp/framework/blob/master/src/Framework/HydeServiceProvider.php)
is registered, at which point the provider will search for any overrides in the config file.

**This means that there are two options to change the source and output directories:**
1. **Recommended:** You can change the values in the config file, to let the `HydeServiceProvider` handle it for you.
2. **Advanced/Overkill:** You can also set the static properties directly in the page model classes if you prefer.
   - You'd probably want to do this in a service provider, as it must be done before the Kernel is booted.
   - You can use the [`RegistersFileLocations`](https://github.com/hydephp/framework/blob/master/src/Framework/Concerns/RegistersFileLocations.php)
     trait to use the same registration logic as the `HydeServiceProvider`.

## Customizing Source Directories

The directories you place your content in are important. The directory will be used to determine the proper page type and the templates used.
If you are not happy the with defaults, you can change them. Note that these are relative to the `source_root` setting,
which is the root of your project, by default.

Note that you need to take care of conflicts when changing the source directories. For example, if you store Markdown
posts in the same directory as documentation pages, Hyde will not know which page type to use.

### In the config file

```php
// filepath config/hyde.php

'source_directories' => [
    HtmlPage::class => '_pages',
    BladePage::class => '_pages',
    MarkdownPage::class => '_pages',
    MarkdownPost::class => '_posts',
    DocumentationPage::class => '_docs',
],
```

### In a service provider

```php
// filepath app/AppServiceProvider.php
use Hyde\Framework\Concerns\RegistersFileLocations;

public function register(): void
{
    $this->registerSourceDirectories([
        HtmlPage::class => '_pages',
        BladePage::class => '_pages',
        MarkdownPage::class => '_pages',
        MarkdownPost::class => '_posts',
        DocumentationPage::class => '_docs',
    ]);
}
```

## Customizing Output Directories

Like source directories, the output directories are also important as they determine the output path for the compiled pages.
>warning Note that changing output directories also affects the route keys, as those are based on the output directory. <p>Scroll down to see the [Route key impact](#route-key-impact) section for more information.</p>

Each option is relative to the site's `output_directory` setting. Setting a value to `''` will output the page to the site root.

### In the config file

```php
// filepath config/hyde.php
'output_directories' => [
    HtmlPage::class => '',
    BladePage::class => '',
    MarkdownPage::class => '',
    MarkdownPost::class => 'posts',
    DocumentationPage::class => 'docs',
],
```

### In a service provider

```php
// filepath app/AppServiceProvider.php
use Hyde\Framework\Concerns\RegistersFileLocations;

public function register(): void
{
    $this->registerOutputDirectories([
        HtmlPage::class => '',
        BladePage::class => '',
        MarkdownPage::class => '',
        MarkdownPost::class => 'posts',
        DocumentationPage::class => 'docs',
    ]);
}
```

### Route key impact

For example, changing the output directory of Markdown posts to `blog` instead of `posts` will change the route key base from `posts` to `blog`.
This means that a file stored as `_posts/hello-world.md` will have the route key `blog/hello-world` instead of `posts/hello-world`,
this may break your site's configuration and links, so you should always verify your site works properly after such a change.
You can learn more about this in the [route key documentation](core-concepts#paths-identifiers-and-route-keys).

## Custom Source Root

HydePHP will by default look for the source directories shown above in the root of your project.
If you're not happy with this, it's easy to change! For example, you might want everything in a `'src'` subdirectory.
That's easy enough, just set the value of the `source_root` setting in `config/hyde.php` to `'src'`, or whatever you prefer!

```php
// filepath config/hyde.php

'source_root' => '', // [TL! --]
'source_root' => 'src', // [TL! ++]
```

### Automatic change

You can even make this change automatically with the `php hyde change:sourceDirectory` command!

```bash
php hyde change:sourceDirectory <name>
```

When run, Hyde will update the source directory setting in the config file, then create the directory if it doesn't exist,
and move all source directories and their content into it.

## Custom Media Directory

The media directory houses assets like images and stylesheets. The default directory is `_media`, and upon building the site,
Hyde will copy all files in this directory to `_site/media` (or whatever your configured output and media directories are).

You can change the path to this directory by setting the `media_directory` option in `config/hyde.php`.

```php
// filepath config/hyde.php
'media_directory' => '_media',
```

#### Next steps

1. Note that you will likely need to manually update `vite.config.js` so Vite can compile the assets correctly.
2. You will of course also need to copy over any existing files from the old directory to the new one.

#### Note that this setting affects both source and output directories

Note that this change will affect both the source and output directories. For example, if you set the value to `assets`,
all files from `assets` will be copied to `_site/assets`. If the setting starts with an underscore, that will be removed
from the output directory, so files in `_assets` will be copied to `_site/assets`.

## Custom Output Directory

>danger <p><strong>Warning: Hyde deletes all files in the output directory before compiling the site.</strong></p> <p>Don't set this path to a directory that contains important files!</p>

If you want to store your compiled website in a different directory than the default `_site`, you can change the path
using the following configuration option in `config/hyde.php`. The path is expected to be relative to your project root.

```php
// filepath config/hyde.php
'output_directory' => '_site',
```
