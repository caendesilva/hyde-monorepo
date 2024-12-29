---
navigation:
    label: "Advanced Markdown"
    priority: 27
---

# Advanced Markdown

## Introduction

Since HydePHP makes heavy use of Markdown, there are some extra features and helpers created just for Hyde to make using Markdown even easier and more powerful!

## Using Blade in Markdown

A special feature in Hyde, is that you can use [Laravel Blade](https://laravel.com/docs/10.x/blade) in Markdown files!

To use Blade in your Markdown files, simply use the Blade shortcode directive, followed by your desired Blade string.

### Standard syntax

```markdown
 [Blade]: {{ "Hello World!" }} // Will render: 'Hello World!'
```

### Blade includes

Only single-line shortcode directives are supported. If you need to use multi-line Blade code, use an `@include`
directive to render a more complex Blade template. You can pass data to includes by specifying an array to the second argument.

```markdown
 [Blade]: @include("hello-world")
 [Blade]: @include("hello", ["name" => "World"])
```

### Enabling Blade-supported Markdown

The feature is disabled by default since it allows arbitrary PHP to run, which could be a security risk, depending on your setup.
However, if your Markdown is trusted, and you know it's safe, you can enable it in the `config/markdown.php` file.

```php
// filepath: config/markdown.php
'enable_blade' => true,
```

### Limitations

All shortcodes must be the first word on a new line, and only single-line shortcodes are supported.

## Coloured Blockquotes

The HydePHP Markdown converter also supports some extra directives and features. One of them being four different
coloured blockquotes. Simply append the desired colour after the initial `>` character.

```markdown
‎> Normal Blockquote
‎>info Info Blockquote
‎>warning Warning Blockquote
‎>danger Danger Blockquote
‎>success Success Blockquote
```

> Normal Blockquote
>info Info Blockquote
>warning Warning Blockquote
>danger Danger Blockquote
>success Success Blockquote

### Customizations

You can easily customize these styles by publishing and editing the `markdown-blockquote.blade.php` file.

```bash
php hyde publish:views components
```

### Markdown usage

The coloured blockquotes also support inline Markdown, just like normal blockquotes.

```markdown
‎>info Formatting is **supported**!
```

### Limitations

Note that these currently do not support multi-line blockquotes.

## Code Block Filepaths

When browsing these documentation pages you may have noticed a label in the top right corner of code blocks specifying the file path.
These are also created by using a custom Hyde feature that turns code comments into automatic code blocks.

### Usage

Simply add a code comment with the path in the **first line** of a fenced code block like so:

````markdown
// Filepath: _docs/advanced-markdown.md
```php
‎// Filepath: hello-world.php

echo 'Hello World!';
```
````

Which becomes:

```php
// Filepath: hello-world.php

echo 'Hello World!';
```

### Alternative syntax

The syntax is rather forgiving, by design, and supports using both `//` and `#` for comments.
The colon is also optional, and the 'filepath' string is case-insensitive. So the following is also perfectly valid:

````markdown
```js
‎// filepath hello.js
console.log('Hello World!');
```
````

If you have a newline after the filepath, like in the first example, it will be removed so your code stays readable.

### Advanced usage

If you have enabled HTML in Markdown by setting the `allow_html` option to true in your `config/markdown.php` file,
anything within the path label will be rendered as HTML. This means you can add links, or even images to the label.

````markdown
// Filepath: <a href="https://github.com/hydephp/develop/blob/master/docs/digging-deeper/advanced-markdown.md" rel="nofollow noopener" target="_blank">View file on Github</a>
```markdown
‎// Filepath: <a href="https://github.com">View file on Github</a>
```
````

### Limitations

The filepaths are hidden on mobile devices using CSS to prevent them from overlapping with the code block.


## Heading Permalinks

Hyde automatically adds clickable permalink anchors to headings in documentation pages. When you hover over a heading, a `#` link appears that you can click to get a direct link to that section.

### Usage & Configuration

The feature is enabled by default for documentation pages. When enabled, Hyde will automatically add permalink anchors to headings between levels 2-4 (h2-h4). The permalinks are hidden by default and appear when hovering over the heading.

You can enable it for other page types by adding the page class to the `permalinks.pages` array in the `config/markdown.php` file, or disable it for all pages by setting the array to an empty array.

```php
// filepath: config/markdown.php
'permalinks' => [
    'pages' => [
        \Hyde\Pages\DocumentationPage::class,
    ],
],
```

### Advanced Customization

Under the hood, Hyde uses a custom Blade-based heading renderer when converting Markdown to HTML. This allows for more flexibility and customization compared to standard Markdown parsers. You can also publish and customize the Blade component used to render the headings:

```bash
php hyde publish:components
```

This will copy the `markdown-heading.blade.php` component to your views directory where you can modify its markup and behavior.


## Dynamic Markdown Links

HydePHP provides a powerful feature for automatically converting Markdown links to source files to the corresponding routes in the built site.

This allows for a much better writing experience when using an IDE, as you can easily navigate to the source file by clicking on the link. Hyde will then automatically resolve the link to the correct route when building the site, formatting the links properly using dynamic relative paths and your configured `pretty_urls` setting.

## Usage

Using the feature is simple: Just use the source file path when linking to the page you want to resolve:

```markdown
[Home](/_pages/index.blade.php)
[Docs](/_docs/index.md)
[Featured Post](/_posts/hello-world.md)
![Logo](/_media/logo.svg)
```

As you can see, it works for both pages and media assets. The leading slash is optional and will be ignored by Hyde, but including it often gives better IDE support.

### Behind the Scenes

During the build process, HydePHP converts source paths to their corresponding routes and evaluates them depending on the page being rendered.

If your page is in the site root then:

- `/_pages/index.blade.php` becomes `index.html`
- `/_media/logo.svg` becomes `media/logo.svg`

If your page is in a subdirectory then:

- `/_pages/index.blade.php` becomes `../index.html`
- `/_media/logo.svg` becomes `../media/logo.svg`

Of course, if your page is in a more deeply nested directory, the number of `../` will increase accordingly.

We will also match your configured preference for `pretty_urls` and only include the `.html` extension when desired.

### Limitations

There are some limitations and considerations to keep in mind when using this feature:

- This feature will not work for dynamic routes (not backed by a file)
- If you rename a file, links will break. Your IDE may warn about this.
- If a file is not found, we will not be able to see it when evaluating links.
- Relative links are not supported (so `../_pages/index.blade.php` will not work)

## Configuration

### Full configuration reference

All Markdown-related configuration options are in the `config/markdown.php` file.
You can find the full reference on the [Customization](customization#markdown-configuration) page.

### Raw HTML Tags

To convert Markdown, HydePHP uses the GitHub Flavored Markdown extension, which strips out potentially unsafe HTML.
If you want to allow all arbitrary HTML tags, and understand the risks involved, you can enable all HTML tags by setting
the `allow_html` option to `true` in your `config/markdown.php` file.

```php
// filepath: config/markdown.php
'allow_html' => true,
```

This will add and configure the `DisallowedRawHtml` CommonMark extension so that no HTML tags are stripped out.

### Tailwind Typography Prose Classes

HydePHP uses the [Tailwind Typography](https://tailwindcss.com/docs/typography-plugin) to style rendered Markdown.
We do this by adding the `.prose` CSS class to the HTML elements containing the rendered Markdown, using the built-in Blade components.

You can easily edit these classes, for example if you want to customize the prose colours, in the `config/markdown.php` file.

```php
// filepath: config/markdown.php
'prose_classes' => 'prose dark:prose-invert', // [tl! remove]
'prose_classes' => 'prose dark:prose-invert prose-img:inline', // [tl! add]
```

Please note that if you add any new classes, you may need to recompile your CSS file.
