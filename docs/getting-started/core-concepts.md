---
navigation:
    priority: 10
    label: Core Concepts
---

# Core HydePHP Concepts

## Introduction to the Hyde Framework

What makes HydePHP special are its "magic" features like autodiscovery and intelligent data generation.
All designed so that you can focus on your content, while the framework does the heavy lifting.

This page provides a high-level overview of the framework's capabilities, so you can quickly grasp its benefits.
As you delve deeper into the documentation, you'll discover the details of each feature and learn how to leverage them effectively.

## The HydeCLI

When you are not writing Markdown and Blade, most of your interactions with Hyde will be through the command line
using the **HydeCLI**, which is based on the Laravel Artisan Console that you may already be familiar with.

If you want to learn about the available commands and how to use them, you can visit the [Console Commands](console-commands) page,
or you can run any of the built-in help commands to get a list of available commands and their descriptions.

```bash
php hyde list
php hyde help <command>
php hyde <command> [--help]
```

## Directory Structure

To take full advantage of the framework, it may first be good to familiarize ourselves with the directory structure.

| Directory          | Purpose                                                     |
|--------------------|-------------------------------------------------------------|
| `_docs`            | For documentation pages                                     |
| `_posts`           | For blog posts                                              |
| `_pages`           | For static Markdown and Blade pages                         |
| `_media`           | Store static assets to be copied to the build directory     |
| `_site`            | The build directory where your compiled site will be stored |
| `config`           | Configuration files for Hyde and integrations               |
| `resources/assets` | Location for CSS/JS Vite source files (optional)            |
| `resources/views`  | Location for Blade components (optional)                    |
| `app`              | Location for custom PHP classes (optional)                  |

## Page Models

The Hyde page models are an integral part of how HydePHP creates your static site. Each page in your site is represented
by a page model. These are simply PHP classes that in addition to holding both the source content and computed data
for your pages, also house instructions to Hyde on how to parse, process, and render the pages to static HTML.

The page classes are very important and fill two roles:

1. They act as blueprints containing _static_ instructions for how to parse, process, and, render pages.
2. Each class _instance_ also holds the page source contents, as well as the computed data.

To learn more, you can visit the [Page Models](page-models) page.

## File Autodiscovery

Content files, meaning source Markdown and Blade files, are automatically discovered by Hyde and compiled to HTML when
building the site. This means that you don't need to worry about routing and controllers!

The directory a source file is in will determine the Blade template that is used to render it.

All source and output directories are configurable, but the defaults are as follows:

| Page/File Type | Source Directory | Output Directory | File Extensions     |
|----------------|------------------|------------------|---------------------|
| Static Pages   | `_pages/`        | `_site/`         | `.md`, `.blade.php` |
| Blog Posts     | `_posts/`        | `_site/posts/`   | `.md`               |
| Documentation  | `_docs/`         | `_site/docs/`    | `.md`               |
| Media Assets   | `_media/`        | `_site/media/`   | Common asset types  |

## Paths, Identifiers, and Route Keys

Since HydePHP automatically discovers and compiles content files, it is important to understand how HydePHP handles paths,
as the file names and directories they are in will directly influence how the page will be compiled.

As such, it will be helpful for you to know about the following terms:

- **Path:** The full path to a file, including the file name, directory, and extension.
- **Identifier:** The unique identifier for a page. Unique only within the same page type.
- **Route key:** The key used to access the page in the routing system. Unique across all site pages.

Both the identifier and route key are derived from the path of the page. The identifier is the path without the file
extension, and relative to the page type source directory. The route key is the output directory plus the identifier.

The identifier generation can be visualized as follows, where the identifier is underlined:

<pre><code class="torchlight" style="background-color: #292D3E; --theme-selection-background: #00000080;">  <span style="opacity: 0.75">_pages/</span><u>about/contact</u><span style="opacity: 0.75">.md</span></code></pre>

For a Markdown page, like the example above, the route key would be the same as the identifier, since Markdown pages are
output to the site root. If it was a Markdown post however, the route key would be: `posts/about/contact`.

This can be visualized as follows, assuming a blog post is stored as `_posts/hello-world.md`:

<pre><code class="torchlight" style="background-color: #292D3E; --theme-selection-background: #00000080;">  <span style="opacity: 0.75">_site/</span><u>posts/hello-world</u><span style="opacity: 0.75">.html</span></code></pre>

As you can see, the route key is simply put the relative page URL, without the .html extension.

## Convention Over Configuration

Hyde favours the "Convention over Configuration" paradigm and thus comes preconfigured with sensible defaults.
However, Hyde also strives to be modular and endlessly customizable hackable if you need it.
Take a look at the [customization and configuration guide](customization) to see the endless options available!

## Front Matter

>info **In a nutshell:** Front Matter is a block of YAML containing metadata, stored at the top of a Markdown file.

Front matter is heavily used in HydePHP to store metadata about pages. Hyde uses the front matter data to generate rich and dynamic content. For example, a blog post category, author website, or featured image.

Using front matter is optional, as Hyde will dynamically generate data based on the content itself. (Though any matter you provide will take precedence over the automatically generated data.)

To learn more, you can visit the [Front Matter](front-matter) page.

### Front Matter in Markdown

All Markdown content files support Front Matter, and blog posts make heavy use of it. Here's what it may look like:

```markdown
---
title: "My New Post"
author: "Mr Hyde"
date: "2023-03-14"
---

## Markdown Comes Here

Lorem ipsum dolor sit amet, etc.
```

### Front Matter in Blade

HydePHP has experimental support for creating front-matter in Blade templates, called [BladeMatter](front-matter#front-matter-in-blade),
where code in `@php` directives are statically parsed into page object's front matter data where it can be accessed in your templates.

```blade
@php($title = 'BladeMatter Demo') // Equivalent to `title: 'BladeMatter Demo'` in Yaml
```

## Automatic Routing

>info **In a nutshell:** Hyde will automatically create routes for your source files.

If you've ever worked in an MVC framework, you are probably familiar with the concept of routing.
And you are probably also familiar with how boring and tedious it can be. Thankfully, Hyde takes the pain out of routing by doing it for you!

During the Autodiscovery process. Hyde will automatically discover all the content files in the source directories,
and create routes for all of them, and store them in an index which works as a two-way link between source files and compiled files.

You can see all the routes and their corresponding source files by running the `hyde route:list` command.

```bash
php hyde route:list
```

To access routes in your code, simply use the Routes facade and specify the route key for the desired page.

```php
Routes::get('posts/my-post')
```

To learn more about the routing system, please visit the [routing documentation](automatic-routing).

## Global Page Data

During the build of each page, Hyde will inject some data available to all Blade views. If you are not planning to write
any custom Blade templates, you can safely ignore this section. If you are, here are the three global variables you can use:

- `$page`: The [Page Object](#page-models) for the current page.
- `$route`: The [Route Object](#automatic-routing) for the current page.
- `$routeKey`: The [Route Key](#paths-identifiers-and-route-keys) for the current page.

The `$page` variable is likely to the most important one, as it contains all the data for the current page.
Depending on the page type, you will have different helpers available. But `$page->matter()` is likely to be very helpful.

You can see all the helpers in the [Page API](hyde-pages) reference page.

## Terminology

In this quick reference, we'll briefly go over some terminology and concepts used in HydePHP.
This will help you understand the documentation and codebase better, as well as helping you know what to search for when you need help.

### Table of contents

<div class="lg:flex">

<div style="margin-right: 2rem;">

#### Tools

- [HydePHP](#hydephp)
- [Laravel](#laravel)
- [Composer](#composer)
- [Tailwind CSS](#tailwind-css)

</div>

<div style="margin-right: 2rem;">

#### Languages

- [Front Matter](#front-matter)
- [Markdown](#markdown)
- [Blade](#blade)
- [YAML](#yaml)
- [PHP](#php)
- [HTML](#html)

</div>

<div style="margin-right: 2rem;">

#### General Concepts

- [Static Sites](#static-sites)
- [Version Control](#version-control)
- [Git](#git)

</div>

<div style="margin-right: 2rem;">

#### HydePHP Features

- [Autodiscovery](#autodiscovery)
- [Page Types](#page-types)
- [Page Identifiers](#page-identifiers)
- [Routes](#routes)
- [Route Keys](#route-keys)

</div>

</div>

[//]: # (Languages and Tools)

### HydePHP

HydePHP is a static site generator written in PHP, designed to make it easy for developers to build fast and secure websites.
It uses a simple directory structure and templating system to generate static websites from content files,
and can be easily extended using PHP libraries and packages.

### Laravel

Laravel is the PHP framework that HydePHP is built on top of. We use a specialized version called Laravel Zero,
which is optimized for command-line applications.

### Front Matter

Front Matter is a block of YAML, stored at the top of a Markdown file, enclosed by a set of triple-dashed lines.
It is commonly used to store metadata about the content, such as the title, author, date, etc.

### Markdown

Markdown is a lightweight markup language that uses plain text formatting syntax, designed to make it easy to create
structured content for the web. HydePHP uses Markdown as the base for most of its content files.

### Blade

Blade is the templating engine from Laravel, which allows developers to write clean and reusable code for the
presentation layer of web applications. HydePHP uses Blade both for the built-in views and components,
as well as to provide powerful templating capabilities through Blade-based pages.

### YAML

YAML is a human-readable data serialization format used for configuration files and often used as the syntax for
Front Matter in HydePHP content files. YAML is designed to be easily read by humans and parsed by machines,
making it a popular choice for many applications and frameworks.

### PHP

PHP is a popular server-side scripting language used for web development that can be embedded in HTML.
HydePHP is built on top of PHP and utilizes its powerful capabilities for generating static websites.

### HTML

HTML (Hypertext Markup Language) is the standard markup language used to create web pages and web applications.
HydePHP uses HTML to render the static websites generated from its content files and templates.

### Tailwind CSS

Tailwind CSS is a utility-first CSS framework used for rapidly building custom user interfaces.
HydePHP supports Tailwind CSS out of the box through the built-in Blade templates,
making it easy for developers to create beautiful and responsive websites without writing custom CSS.

### Composer

Composer is a dependency manager for PHP that simplifies the process of installing and managing packages required by
PHP applications. HydePHP uses Composer to manage its own dependencies and make it easy for users to install and use the software.

### Static Sites

A static website is a collection of HTML web pages that are delivered to the user's web browser exactly as they are stored
on the web server. HydePHP generates static websites, making them fast, secure, and easy to deploy.

### Version Control

HydePHP can be easily integrated with Git to manage website source files and track changes over time,
as one of the many benefits with static sites is that they are designed to be version controlled.

### Git

Git is a free and open-source distributed version control system that is widely used for software development.
Git repositories can be hosted on GitHub, GitLab, BitBucket, or any other Git hosting service.

[//]: # (HydePHP Features)

### Autodiscovery

Content files, including Markdown and Blade files, are automatically discovered and compiled to HTML during site builds.
During autodiscovery, Hyde also generates dynamic data to enrich your content based on the page type.

In short the autodiscovery is split into three steps:
`File discovery -> Page parsing -> Route generation`

### Page Types

All pages in HydePHP are internally represented by a page object that extends the HydePage class. Each page type has its
own page class which acts as a blueprint defining information for the framework to parse a file and generate relevant data.

### Page Identifiers

The page identifier is the name of the file without the file extension, relative to the page type's source directory.
The identifier is used to generate the route key, which is used to generate the file name for the compiled HTML file.

### Routes

All pages are internally bound to a Route object, through the route key. During the build process, each route is
compiled to HTML using the page object's data, and saved to the output directory with a file name created from the route key.
Since routes are generated automatically during autodiscovery, there is no need to create them manually.

### Route Keys

The route key is the URL path relative to the site webroot, without the file extension. The route key is the common
identifier binding Page objects to Route objects, and is used to generate the file name for the compiled HTML file.

Route keys generation can be visualised as follows: `<PageClass::OutputDirectory>/<PageIdentifier>`
