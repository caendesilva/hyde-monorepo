---
navigation:
    priority: 35
    label: "File-based Collections"
---

# File-based Collections

## Introduction to Hyde Data Collections

Hyde provides `DataCollection`, a subset of [Laravel Collections](https://laravel.com/docs/10.x/collections) giving you a similar developer experience to working with Eloquent Collections.
However, instead of accessing a database, it's all entirely file-based using static data files such as Markdown, Yaml, and JSON files which get
parsed into objects that you can easily work with.

As you have access to all standard Laravel Collection methods, you are encouraged to read the [Laravel Collections documentation](https://laravel.com/docs/10.x/collections) for more information.

This article covers advanced usage intended for those who are writing their own Blade views, and is not required as Hyde comes pre-packaged with many templates for you to use.

## High-Level Concept Overview

In short, HydePHP finds files in the specified directory, turns each file into an object, and returns a Laravel Collection of these objects.

To make collections easy to use and understand, Hyde makes a few assumptions about the structure of your collections.
Follow these conventions and creating dynamic static sites will be a breeze.

1. Collections are accessed through static methods in the `DataCollection` class.
2. Collections are stored as files in subdirectories of the `resources/collections` directory.
3. To get a collection, specify name of the subdirectory the files are stored in.
4. Data will be parsed into differing objects depending on which facade method you use. See the table below.
5. The class is aliased so that you can use it in Blade files without having to include the namespace.
6. While not enforced, each subdirectory should probably only have the same filetype to prevent developer confusion.
7. Unlike source files for pages, files starting with underscores are not ignored.
8. You can customize the source directory for collections through a service provider.

## Available Collection Types

### Quick Reference Overview

The following facade methods for creating data collections are available:

```php
\Hyde\Support\DataCollection::markdown(string $name);
\Hyde\Support\DataCollection::yaml(string $name);
\Hyde\Support\DataCollection::json(string $name, bool $asArray = false);
```

### Quick Reference Table

| Collection Type                       | Facade Method  | Returned Object Type                                                                                                                                     | File Extension        |
|---------------------------------------|----------------|----------------------------------------------------------------------------------------------------------------------------------------------------------|-----------------------|
| **[Markdown](#markdown-collections)** | `::markdown()` | [`MarkdownDocument`](https://github.com/hydephp/framework/blob/master/src/Markdown/Models/MarkdownDocument.php)                                          | `.md`                 |
| **[Yaml](#yaml-collections)**         | `::yaml()`     | [`FrontMatter`](https://github.com/hydephp/framework/blob/master/src/Markdown/Models/FrontMatter.php)                                                    | `.yaml`,&nbsp; `.yml` |
| **[Json](#json-collections)**         | `::json()`     | [`stdClass`](https://www.php.net/manual/en/class.stdclass.php) <small>OR&nbsp;</small> [`array`](https://www.php.net/manual/en/language.types.array.php) | `.json`               |

## Markdown Collections

### Usage

```php
$collection = \Hyde\Support\DataCollection::markdown('name');
```

### Example returns

Here is an approximation of the data types contained by the variable created above:

```php
\Hyde\Support\DataCollection {
    "testimonials/1.md" => Hyde\Markdown\Models\MarkdownDocument
    "testimonials/2.md" => Hyde\Markdown\Models\MarkdownDocument
    "testimonials/3.md" => Hyde\Markdown\Models\MarkdownDocument
  ]
}
```

The returned MarkdownObjects look approximately like this:

```php
\Hyde\Markdown\Models\MarkdownDocument {
  +matter: Hyde\Markdown\Models\FrontMatter {
     +data: array:1 [
       "author" => "John Doe"
     ]
  }
  +markdown: Hyde\Markdown\Models\Markdown {
    +body: "Lorem ipsum dolor sit amet, consectetur adipiscing elit..."
  }
}
```

Assuming the Markdown document looks like this:

```markdown
---
author: "John Doe"
---

Lorem ipsum dolor sit amet, consectetur adipiscing elit...
```

## YAML Collections

### Usage

```php
$collection = \Hyde\Support\DataCollection::yaml('name');
```

### Example returns

Here is an approximation of the data types contained by the variable created above:

```php
\Hyde\Support\DataCollection {
  "authors/1.yaml" => Hyde\Markdown\Models\FrontMatter {
    +data: array:1 [
      "name" => "John Doe",
      "email" => "john@example.org"
    ]
  }
}
```

Assuming the Yaml document looks like this:

```yaml
name: "John Doe"
email: "john@example.org"
```

## Json Collections

### Usage

```php
$collection = \Hyde\Support\DataCollection::json('name');
```

By default, the entries will be returned as `stdClass` objects. If you want to return an associative array instead, pass `true` as the second parameter:

```php
$collection = \Hyde\Support\DataCollection::json('name', true);
```

Since both return values use native PHP types, there are no example returns added here, as I'm sure you can imagine what they look like.

## Markdown Collections - Hands-on Guide

I think the best way to explain DataCollections is through examples, so let's create a Blade page with customer testimonials!

This example will use Markdown Collections, but the same concepts apply to all other collection types.

#### Setting up the file structure

We start by setting up our directory structure. We will create a `testimonials` subdirectory, which will be the collection name.

In it, we will place Markdown files. Each file will be a testimonial.
The Markdown will be parsed into a `MarkdownDocument` object which parses any optional YAML front matter.

Here is the sample Markdown we will use:

```blade
// filepath: resources/collections/testimonials/1.md
---
author: John Doe
---

Lorem ipsum dolor sit amet, consectetur adipiscing elit...
```

Let's take a look at our directory structure. I just copied the same file a few times.
You can name the files anything you want, I kept it simple and just numbered them.

```tree
resources/collections
└── testimonials
    ├── 1.md
    ├── 2.md
    └── 3.md
```

#### Using the Facade to Access the Collections

Now for the fun part! We will use the `DataCollection::markdown()` to access all our files into a convenient object.
The class is registered with an alias, so you don't need to include any namespaces when in a Blade file.

The general syntax to use the facade is as follows:

```blade
DataCollection::markdown('subdirectory_name')
```

This will return a Hyde DataCollection object, containing our Markdown files as MarkdownDocument objects. Here is a quick look at the object the facade returns:

<pre style="display: block; white-space: pre-wrap; padding: 1rem 1.5rem; overflow: initial !important; background-color: rgb(24, 23, 27); color: rgb(255, 132, 0); font: 400 12px Menlo, Monaco, Consolas, monospace; overflow-wrap: break-word; position: relative; z-index: 99999; word-break: break-all; letter-spacing: normal; orphans: 2; text-align: start; text-indent: 0px; text-transform: none; widows: 2; word-spacing: 0px; -webkit-text-stroke-width: 0px; text-decoration-thickness: initial; text-decoration-style: initial; text-decoration-color: initial;"><span class="sf-dump-default" style="display: inline; background-color: rgb(24, 23, 27); color: rgb(255, 132, 0); font: 12px Menlo, Monaco, Consolas, monospace; overflow-wrap: break-word; white-space: pre-wrap; position: relative; z-index: 99999; word-break: break-all;">^</span><span class="sf-dump-default" style="display: inline; background-color: rgb(24, 23, 27); color: rgb(255, 132, 0); font: 12px Menlo, Monaco, Consolas, monospace; overflow-wrap: break-word; white-space: pre-wrap; position: relative; z-index: 99999; word-break: break-all;"> </span><span style="display: inline; color: rgb(18, 153, 218);">Hyde\Support\DataCollection</span> {<span style="text-decoration: none; border: 0px; outline: none; color: rgb(160, 160, 160);">#651 <span style="display: inline;">▼</span></span><samp>
  #<span style="display: inline; color: rgb(255, 255, 255);">items</span>: <span style="display: inline; color: rgb(18, 153, 218);">array:3</span> [<span style="text-decoration: none; border: 0px; outline: none; color: rgb(160, 160, 160);"><span style="display: inline;">▼</span></span><samp>
    "<span style="display: inline; color: rgb(86, 219, 58);">testimonials/1.md</span>" =&gt; <span style="display: inline; color: rgb(18, 153, 218);"><span>Hyde\Markdown\Models</span><span style="display: inline-block; text-overflow: ellipsis; max-width: none; white-space: nowrap; overflow: hidden; vertical-align: top; color: rgb(18, 153, 218);">\</span>MarkdownDocument</span> {<span style="text-decoration: none; border: 0px; outline: none; color: rgb(160, 160, 160);">#653 <span style="display: inline;">▼</span></span><samp>
      +<span style="display: inline; color: rgb(255, 255, 255);">matter</span>: <span style="display: inline; color: rgb(18, 153, 218);"><span>Hyde\Markdown\Models</span><span style="display: inline-block; text-overflow: ellipsis; max-width: none; white-space: nowrap; overflow: hidden; vertical-align: top; color: rgb(18, 153, 218);">\</span>FrontMatter</span> {<span style="text-decoration: none; border: 0px; outline: none; color: rgb(160, 160, 160);">#652 <span style="display: inline;">▶</span></span>}
      +<span style="display: inline; color: rgb(255, 255, 255);">markdown</span>: <span style="display: inline; color: rgb(18, 153, 218);"><span>Hyde\Markdown\Models</span><span style="display: inline-block; text-overflow: ellipsis; max-width: none; white-space: nowrap; overflow: hidden; vertical-align: top; color: rgb(18, 153, 218);">\</span>Markdown</span> {<span style="text-decoration: none; border: 0px; outline: none; color: rgb(160, 160, 160);">#654 <span style="display: inline;">▶</span></span>}
    </samp>}
    "<span style="display: inline; color: rgb(86, 219, 58);">testimonials/2.md</span>" =&gt; <span style="display: inline; color: rgb(18, 153, 218);"><span>Hyde\Markdown\Models</span><span style="display: inline-block; text-overflow: ellipsis; max-width: none; white-space: nowrap; overflow: hidden; vertical-align: top; color: rgb(18, 153, 218);">\</span>MarkdownDocument</span> {<span style="text-decoration: none; border: 0px; outline: none; color: rgb(160, 160, 160);">#656 <span style="display: inline;">▶</span></span>}
    "<span style="display: inline; color: rgb(86, 219, 58);">testimonials/3.md</span>" =&gt; <span style="display: inline; color: rgb(18, 153, 218);"><span>Hyde\Markdown\Models</span><span style="display: inline-block; text-overflow: ellipsis; max-width: none; white-space: nowrap; overflow: hidden; vertical-align: top; color: rgb(18, 153, 218);">\</span>MarkdownDocument</span> {<span style="text-decoration: none; border: 0px; outline: none; color: rgb(160, 160, 160);">#659 <span style="display: inline;">▶</span></span>}
  </samp>]
</samp>}
</pre>

#### Implementing it in a Blade view

Let's create a Blade page to display all our testimonials.

```bash
php hyde make:page "Testimonials" --type="blade"
```

And we can use the collection almost like any other Laravel one. As you can see, since each entry is a `MarkdownDocument` class,
we are able to get the author from the front matter, and the content from the body.

```blade
// filepath _pages/testimonials.blade.php
@foreach(DataCollection::markdown('testimonials') as $testimonial)
    <blockquote>
        <p>{{ $testimonial->body }}</p>
        <small>{{ $testimonial->matter['author'] }}</small>
    </blockquote>
@endforeach
```
