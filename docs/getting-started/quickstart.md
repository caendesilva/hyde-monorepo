---
navigation:
    label: Quickstart Guide
    priority: 1
---

# Quickstart Guide

## Installing HydePHP Using Composer

The recommended method of installing Hyde is using Composer, which installs the required dependencies on a per-project basis.

```bash
composer create-project hyde/hyde
```

### Requirements

Hyde is based on [Laravel 10](https://laravel.com/docs/10.x/releases)
which requires a minimum PHP version of 8.1.
You should also have [Composer](https://getcomposer.org/) installed.

To use some features like [compiling your own assets](managing-assets)
you also need NodeJS and NPM.

## Using the Hyde CLI

The main way to interact with Hyde is through the [HydeCLI](console-commands), a Laravel Artisan-based command-line interface. Learn more about the HydeCLI in the [console commands](console-commands) documentation.

## Starting a Development Server

To make previewing your site a breeze you can use the realtime compiler, which builds your pages on the fly.

```bash
php hyde serve
```
Simply run the serve command, and you will be able to preview your site at [http://localhost:8080](http://localhost:8080).

## Creating Content

### Directory structure

Creating content with Hyde is easy! Simply place source files in one of the source directories,
and Hyde will automatically discover, parse, and compile them to static HTML.
The directory and file extension of a source file will determine how HydePHP parses and compiles it.
Please see the [directory structure](core-concepts#directory-structure) section for more information.

### Scaffolding files

You can scaffold blog post files using the `php hyde make:post` command which automatically creates the front matter, based on your input selections.
You can also scaffold pages with the `php hyde make:page` command.

```bash
php hyde make:post
php hyde make:page
```

### Compiling to static HTML

Now that you have some amazing content, you'll want to compile your site into static HTML. Thankfully, this is as easy as executing the `build` command, after which your compiled site is stored in the `_site` directory.

```bash
php hyde build
```

When building the site, Hyde will scan your source directories for files and compile them into static HTML using the appropriate layout depending
on what kind of page it is. You don't have to worry about routing as Hyde takes care of everything, including creating navigation menus!

### Managing assets

Hyde comes bundled with a precompiled and minified `app.css` file, containing all the Tailwind you need for the default views meaning that you don't even need to use NPM. However, Hyde is already configured to use Vite to compile your assets if you feel like there's a need to build the assets yourself. See more on the [Managing Assets](managing-assets) page.

### Deploying your site

You are now ready to show your site to the world! Simply copy the `_site` directory to your web server's document root, and you're ready to go.

You can even use GitHub Pages to host your site for free. That's what the Hyde website does, using an Actions CI workflow that automatically builds and deploys this site.

## Further Reading

Here's some ideas of what to read next:

- [Architecture Concepts & Directory Structure](core-concepts)
- [Console Commands with the HydeCLI](console-commands)
- [Creating Blog Posts](blog-posts)
