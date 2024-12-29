---
navigation:
    label: Documentation Pages
    priority: 12
---

# Creating Documentation Pages

## Introduction to Hyde Documentation Pages

Welcome to the Hyde Documentation Pages, where creating professional-looking documentation sites has never been easier. Using the Hyde Documentation module, all you need to do is place standard Markdown files in the `_docs/` directory, and Hyde takes care of the rest.

Hyde compiles your Markdown content into beautiful static HTML pages using a TailwindCSS frontend, complete with a responsive sidebar that is automatically generated based on your Markdown files. You can even customize the order, labels, and groups of the sidebar items to suit your needs.

Additionally, if you have an `_docs/index.md` file, the sidebar header will link to it, and an automatically generated "Docs" link will be added to your site's main navigation menu, pointing to your documentation page.

If you have a Torchlight API token in your `.env` file, Hyde will even enable syntax highlighting automatically, saving you time and effort. For more information about this feature, see the [extensions page](extensions#torchlight).

### Best Practices and Hyde Expectations

Since Hyde does a lot of things automatically, there are some things you may need to keep in mind when creating documentation pages so that you don't get unexpected results.

#### Filenames

- Hyde Documentation pages are files stored in the `_docs` directory
- The filename is used as the filename for the compiled HTML
- Filenames should use `kebab-case-name` format, followed by the appropriate extension
- Files prefixed with `_underscores` are ignored by Hyde
- You should always have an `index.md` file in the `_docs/` directory
- Your page will be stored in `_site/docs/<identifier>.html` unless you [change it in the config](#output-directory)

### Advanced Usage and Customization

Like most of HydePHP, the Hyde Documentation module is highly customizable. Much of the frontend is composed using Blade templates and components, which you can customize to your heart's content.
Since there are so many components, it's hard to list them all here in the documentation, so I encourage you to check out the [source code](https://github.com/hydephp/framework/tree/master/resources/views/components/docs) to see how it's all put together and find the customizations you are looking for.

## Creating Documentation Pages

You can create a Documentation page by adding a file to the `_docs` directory where the filename ends in `.md`.

You can also scaffold one quickly by using the [HydeCLI](console-commands).

```bash
php hyde make:page "Page Title" --type="docs"
```

This will create the following file saved as `_docs/page-title.md`

```markdown
# Page Title

```

### Front Matter Is Optional

You don't need to use [front matter](blog-posts#supported-front-matter-properties) to create a documentation page.

However, Hyde still supports front matter here as it allows you to quickly override the default values.

Here is a quick reference, however, you should take a look at the [dynamic content](#dynamic-content-generation) section to learn more.

```yaml
---
title: "Page Title"
navigation:
    label: "Sidebar Label"
    hidden: true
    priority: 5
---
```

## Dynamic Content Generation

Hyde makes documentation pages easy to create by automatically generating dynamic content such as the sidebar and page title. If you are not happy with the results you can customize them in the config or with front matter.

### Front Matter Reference

Before we look at how to override things, here is an overview of the relevant content Hyde generates, and where the data is from as well as where it can be overridden.

| Property                        | Description                                            | Dynamic Data Source            | Override in          |
|---------------------------------|--------------------------------------------------------|--------------------------------|----------------------|
| `title` (string)                | The title of the page used in the HTML `<title>` tag   | The first H1 heading (`# Foo`) | Front matter         |
| `navigation.label` (string)     | The label for the page shown in the sidebar            | The page basename              | Front matter, config |
| `navigation.priority` (integer) | The priority of the page used for ordering the sidebar | Defaults to 999                | Front matter, config |
| `navigation.hidden` (boolean)   | Hides the page from the sidebar                        | _none_                         | Front matter, config |
| `navigation.group` (string)     | The group the page belongs to in the sidebar           | Subdirectory, if nested        | Front matter         |

## Sidebar

The sidebar is automatically generated from the files in the `_docs` directory. You will probably want to change the order of these items. You can do this in two ways, either in the config or with front matter using the navigation array settings.

Since this feature shares a lot of similarities and implementation details with the navigation menu, I recommend you read the [navigation menu documentation](navigation) page as well to learn more about the fundamentals and terminology.

### Sidebar Ordering

The sidebar is sorted/ordered by the `priority` property. The lower the priority value, the higher up in the sidebar it will be. The default priority is 999. You can override the priority using the following front matter:

```yaml
navigation:
    priority: 5
```

You can also change the order in the `config/docs.php` configuration file, which may be easier to manage for larger sites.

#### Basic Priority Syntax

A nice and simple way to define the order of pages is to add their route keys as a simple list array. Hyde will then match that array order.

It may be useful to know that Hyde internally will assign a priority calculated according to its position in the list, plus an offset of `500`. The offset is added to make it easier to place pages earlier in the list using front matter or with explicit priority settings.

```php
// filepath: config/docs.php
'sidebar' => [
    'order' => [
        'readme', // Priority: 500
        'installation', // Priority: 501
        'getting-started', // Priority: 502
    ]
]
```

#### Explicit Priority Syntax

You can also specify explicit priorities by adding a value to the array keys. Hyde will then use these exact values as the priorities.

```php
// filepath: config/docs.php
'sidebar' => [
    'order' => [
        'readme' => 10,
        'installation' => 15,
        'getting-started' => 20,
    ]
]
```

### Sidebar Labels

The sidebar items are labelled with the `label` property. The default label is generated from the filename of the file.

You can change it with the following front matter:

```yaml
navigation:
    label: "My Custom Sidebar Label"
```

### Sidebar Grouping

Sidebar grouping allows you to group items in the sidebar under category headings. This is useful for creating a sidebar with a lot of items. The official HydePHP.com docs, for instance, use this feature.

The feature is enabled automatically when one or more of your documentation pages have the `navigation.group` property set in the front matter, or when documentation pages are organized in subdirectories. Once activated, Hyde will switch to a slightly more compact sidebar layout with pages sorted into labeled groups. Any pages without the group information will be put in the "Other" group.

#### Using Front Matter

To enable sidebar grouping, you can add the following front matter to your documentation pages:

```yaml
navigation:
    group: "Getting Started"
```

#### Automatic Subdirectory-Based Grouping

You can also automatically group your documentation pages by placing source files in subdirectories.

For example, putting a Markdown file in `_docs/getting-started/` is equivalent to adding the same front matter seen above.

>warning Note that when the [flattened output paths](#using-flattened-output-paths) setting is enabled (which it is by default), the file will still be compiled to the `_site/docs/` directory like it would be if you didn't use the subdirectories. Note that this means that you can't have two documentation pages with the same filename as they would overwrite each other.

### Hiding Items

You can hide items from the sidebar by adding the `hidden` property to the front matter:

```yaml
navigation:
    hidden: true
```

This can be useful to create redirects or other items that should not be shown in the sidebar.

>info The index page is by default not shown as a sidebar item, but instead is linked in the sidebar header.

## Customization

Please see the [customization page](customization) for in-depth information on how to customize Hyde, including the documentation pages. Here is a high level overview for quick reference though.

### Output Directory

If you want to store the compiled documentation pages in a different directory than the default 'docs' directory, for example to specify a version like the Hyde docs does, you can specify the output directory in the Hyde configuration file.

The path is relative to the site output, typically `_site`.

```php
// filepath: config/hyde.php
'output_directories' => [
    \Hyde\Pages\DocumentationPage::class => 'docs' // default [tl! --]
    \Hyde\Pages\DocumentationPage::class => 'docs/1.x' // What the Hyde docs use [tl! ++]
]
```

Note that you need to take care as to not set it to something that may conflict with other parts, such as media or posts directories.

### Automatic Navigation Menu

By default, a link to the documentation page is added to the navigation menu when an index.md file is found in the `_docs` directory. Please see [the customization page](customization#navigation-menu--sidebar) for more information.

### Sidebar Header Name

By default, the site title shown in the sidebar header is generated from the configured site name suffixed with "docs". You can change this in the Docs configuration file. Tip: The header will link to the docs/index page, if it exists.

```php
'title' => 'API Documentation',
```

### Sidebar Footer Customization

The sidebar footer contains, by default, a link to your site homepage. You can change this in the `config/docs.php` file.

```php
// filepath: config/docs.php

'sidebar' => [
    'footer' => 'My **Markdown** Footer Text',
],
```

You can also set the option to `false` to disable it entirely.

### Sidebar Page Order

To quickly arrange the order of items in the sidebar, you can reorder the page identifiers in the list and the links will be sorted in that order. Link items without an entry here will fall back to the default priority of 999, putting them last.

```php
'sidebar' => [
    'order' => [
        'readme',
        'installation',
        'getting-started',
    ],
]
```

See [the chapter in the customization page](customization#navigation-menu--sidebar) for more details. <br>

### Setting Sidebar Group Labels

When using the automatic sidebar grouping feature the titles of the groups are generated from the subdirectory names. If these are not to your liking, for example if you need to use special characters, you can override them in the configuration file. The array key is the directory name, and the value is the label.

```php
// Filepath: config/docs.php

'sidebar_group_labels' => [
    'questions-and-answers' => 'Questions & Answers',
],
```

Please note that this option is not added to the config file by default, as it's not a super common use case. No worries though, just add the following yourself!

#### Setting Sidebar Group Priorities

By default, each group will be assigned the lowest priority found inside the group. However, you can specify the order and priorities for sidebar group keys the same way you can for the sidebar items.

Just use the sidebar group key as instead of the page identifier/route key:

```php
// Filepath: config/docs.php
'sidebar' => [
    'order' => [
        'readme',
        'installation',
        'getting-started',
    ],
],
```

### Numerical Prefix Sidebar Ordering

HydePHP v2 introduces sidebar item ordering based on numerical prefixes in filenames. This feature works for the documentation sidebar.

This has the great benefit of matching the sidebar layout with the file structure view. It also works especially well with subdirectory-based sidebar grouping.

```shell
_docs/
  01-installation.md     # Priority: 1
  02-configuration.md    # Priority: 2
  03-usage.md            # Priority: 3
```

As you can see, Hyde parses the number from the filename and uses it as the priority for the page in the sidebar, while stripping the prefix from the route key.

#### Important Notes

1. The numerical prefix remains part of the page identifier but is stripped from the route key.
   For example: `_docs/01-installation.md` has route key `installation` and page identifier `01-installation`.
2. You can delimit the numerical prefix with either a dash or an underscore.
   For example: Both `_docs/01-installation.md` and `_docs/01_installation.md` are valid.
3. Leading zeros are optional. `_docs/1-installation.md` is equally valid.

#### Using Numerical Prefix Ordering in Subdirectories

This feature integrates well with automatic subdirectory-based sidebar grouping. Here's an example of how you could organize a documentation site:

```shell
_docs/
  01-getting-started/
    01-installation.md
    02-requirements.md
    03-configuration.md
  02-usage/
    01-quick-start.md
    02-advanced-usage.md
  03-features/
    01-feature-1.md
    02-feature-2.md
```

Here are two useful tips:

1. You can use numerical prefixes in subdirectories to control the sidebar group order.
2. The numbering within a subdirectory works independently of its siblings, so you can start from one in each subdirectory.

### Table of Contents Settings

Hyde automatically generates a table of contents for the page and adds it to the sidebar.

In the `config/docs.php` file you can configure the behaviour, content, and the look and feel of the sidebar table of contents. You can also disable the feature completely.

```php
'sidebar' => [
    'table_of_contents' => [
        'enabled' => true,
        'min_heading_level' => 2,
        'max_heading_level' => 4,
    ],
],
```

To customize the markup or styles of the table of contents, you can publish the `x-hyde::docs.table-of-contents` Blade component and modify it to your liking.

### Using Flattened Output Paths

If this setting is set to true, Hyde will output all documentation pages into the same configured documentation output directory. This means that you can use the automatic directory-based grouping feature, but still have a "flat" output structure. Note that this means that you can't have two documentation pages with the same filename or navigation menu label as they will overwrite each other.

If you set this to false, Hyde will match the directory structure of the source files (just like all other pages).

```php
// Filepath: config/docs.php
'flattened_output_paths' => true,
```

## Search Feature

### Introduction

Hyde includes a built-in search feature for documentation pages powered by Alpine.js. It consists of two parts:
1. A search index generator that runs during the build command
2. An Alpine.js powered frontend that provides the search interface

>info Tip: The search feature is what powers the search on this site! Why not [try it out](search)?

The search feature is enabled by default. You can disable it by removing the `DocumentationSearch` option from the Hyde `Features` config array:

```php
// filepath: config/hyde.php
'features' => [
    Feature::DocumentationSearch, // [tl! --]
],
```

### Using the Search

The search works by generating a JSON search index which Alpine.js loads asynchronously. There are two ways to access the search:

1. A full-page search screen at `docs/search.html`
2. A modal dialog accessible via a button in the documentation pages (similar to Algolia DocSearch). You can also open this dialog using the keyboard shortcut `/`

>info The full page can be disabled by setting `create_search_page` to `false` in the `docs` config.

### Search Features

The search implementation includes:
- Real-time search results as you type
- Context highlighting of search terms
- Match counting and search timing statistics
- Dark mode support
- Loading state indicators
- Keyboard navigation support
- Mobile-responsive design

### Hiding Pages from Indexing

For large pages like changelogs, you may want to exclude them from the search index. Add the page identifier to the `exclude_from_search` array in the docs config:

```php
// filepath: config/docs.php
'exclude_from_search' => [
  'changelog',
]
```

The page will remain accessible but won't appear in search results.

### Live Search with the Realtime Compiler

When using `php hyde serve`, the Realtime Compiler automatically generates a fresh search index each time it's requested, ensuring your search results stay current during development.

## Automatic "Edit Page" Button

### Introduction

Hyde can automatically add links to documentation pages that take the user to a GitHub page (or similar) to edit the page. This makes it great for open-source projects looking to allow others to contribute to the documentation in a quick and easy manner.

The feature is automatically enabled when you specify a base URL in the Docs configuration file. Hyde expects this to be a GitHub path, but it will probably work with other methods as well. If not, please send a PR and/or create an issue on the [GitHub repository](https://github.com/hydephp/framework)!

>info Tip: This documentation site uses this feature, scroll down to the bottom of this page and try it out!

### Configuration

Here's an example configuration from the official HydePHP.com documentation:

```php
// Filepath: config/docs.php

'source_file_location_base' => 'https://github.com/hydephp/docs/blob/master/',
```

#### Changing the Button Text

Changing the label is easy, just change the following config setting:

```php
// Filepath: config/docs.php
'edit_source_link_text' => 'Edit Source on GitHub',
```

#### Changing the Position

By default, the button will be shown in the documentation page footer. You can change this by setting the following config setting to `'header'`, `'footer'`, or `'both'`

```php
// Filepath: config/docs.php
'edit_source_link_position' => 'header',
```

#### Adding a Button Icon

This is not included out of the box, but is easy to add with some CSS! Just target the `.edit-page-link` class.

```css
// filepath e.g. app.css
.edit-page-link::before {content: "✏ "}
```

#### Changing the Blade View

You can also publish the `edit-source-button.blade.php` view and change it to your liking.
