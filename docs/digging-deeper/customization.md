---
navigation:
    label: "Customizing your Site"
    priority: 25
---

# Customizing Your Site

## Introduction

Hyde favours <a href="https://en.wikipedia.org/wiki/Convention_over_configuration">"Convention over Configuration"</a>
and comes preconfigured with sensible defaults. However, Hyde also strives to be modular and endlessly customizable
if you need it. This page guides you through the many options available!

All the configuration files are stored in the config directory, and allow you to customize almost all aspects of your site.
Each option is documented, so feel free to look through the files and get familiar with the options available to you.

## Accessing Configuration Values

### Configuration API Recap

HydePHP uses the same configuration system as Laravel. Here's a quick recap from the [Laravel Documentation](https://laravel.com/docs/10.x/configuration):

You may easily access your configuration values using the global `config` function from anywhere in your project code.
The configuration values may be accessed using "dot notation" syntax, which includes the name of the file and option you wish to access.

```php
$value = config('hyde.name');
```

A default value may also be specified and will be returned if the configuration option does not exist:

```php
$value = config('hyde.name', 'HydePHP');
```

HydePHP also provides a strongly typed `Config` facade which extends the Laravel `Config` facade, but allows strict types:

```php
use Hyde\Facades\Config;

// Will always return a string, or it throws a TypeError
$name = Config::getString('hyde.name', 'HydePHP'): string;
```

### Dot Notation

As seen in the example above, when referencing configuration options, we often use "dot notation" to specify the configuration file.
For example, `config('hyde.name')` means that we are looking for the `name` option in the `config/hyde.php` file.

### Front Matter or Configuration Files?

In some cases, the same options can be set in the front matter of a page or in a configuration file. Both ways are always documented, and it's up to you to choose which one you prefer. Note that in most cases, if a setting is set in both the front matter and the configuration file, the front matter setting will take precedence.

### A note on file paths

When Hyde references files, especially when passing filenames between components, the file path is almost always
relative to the root of the project. Specifying absolute paths yourself could lead to unforeseen problems.

## Configuration Files Overview

There are a few configuration files available in the `config` directory. All options are documented, so feel free to look through the files and get familiar with the options available to you.

Below are two tables over the different configuration files. Click on a file name to see the default file on GitHub.

### HydePHP Configuration Files

These are the main configuration files for HydePHP and lets you customize the look and feel of your site, as well as the behaviour of HydePHP itself.
The main configuration file, `hyde.php`, is used for things ranging from site name and base URL to navigation menus and what features to enable.

| Config File                                                                                                        | Description                                                                                       |
|--------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------|
| <a href="https://github.com/hydephp/hyde/blob/master/config/hyde.php" rel="nofollow noopener">hyde.php</a>         | Main HydePHP configuration file for customizing the overall project.                              |
| <a href="https://github.com/hydephp/hyde/blob/master/config/docs.php" rel="nofollow noopener">docs.php</a>         | Options for the HydePHP documentation site generator module.                                      |
| <a href="https://github.com/hydephp/hyde/blob/master/config/markdown.php" rel="nofollow noopener">markdown.php</a> | Configure Markdown related services, as well as change the CommonMark extensions.                 |
| <a href="https://github.com/hydephp/hyde/blob/master/app/config.php" rel="nofollow noopener">app/config.php</a>    | Configures the underlying Laravel application. (Commonly found as config/app.php in Laravel apps) |

>info Tip: The values in `hyde.php` can also be set in YAML by creating a `hyde.yml` file in the root of your project. See [#yaml-configuration](#yaml-configuration) for more information.

### Publishable Laravel & Package Configuration Files

Since HydePHP is based on Laravel we also have a few configuration files related to them. As you most often don't need
to edit any of these, unless you want to make changes to the underlying application, they are not present in the
base HydePHP installation. However, you can publish them to your project by running `php hyde publish:configs`.

| Config File                                                                                                            | Description                                                             |
|------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------|
| <a href="https://github.com/hydephp/hyde/blob/master/config/view.php" rel="nofollow noopener">view.php</a>             | Configures the paths for the Blade View compiler.                       |
| <a href="https://github.com/hydephp/hyde/blob/master/config/cache.php" rel="nofollow noopener">cache.php</a>           | Configures the cache driver and cache path locations.                   |
| <a href="https://github.com/hydephp/hyde/blob/master/config/commands.php" rel="nofollow noopener">commands.php</a>     | Configures the Laravel Zero commands for the HydeCLI.                   |
| <a href="https://github.com/hydephp/hyde/blob/master/config/torchlight.php" rel="nofollow noopener">torchlight.php</a> | Configures settings for the Torchlight syntax highlighting integration. |

{.align-top}

If any of these files are missing, you can run `php hyde publish:configs` to copy the default files to your project.

## Configuration Options

While all options are already documented within the files, here are some further explanations of some of the options.

### RSS feed generation

When enabled, an RSS feed containing all your Markdown blog posts will be generated when you compile your static site.
Here are the default settings:

```php
// filepath config/hyde.php
'rss' => [
    // Should the RSS feed be generated?
    'enabled' => true,

    // What filename should the RSS file use?
    'filename' => 'feed.xml',

    // The channel description.
    'description' => env('SITE_NAME', 'HydePHP').' RSS Feed',
],
```

>warning Note that this feature requires that a `site_url` is set!

### Authors

Hyde supports adding authors to blog posts, allowing you to automatically include information like display names and website links.
We even support fields for avatars, biographies, and social media profiles, which you can use in your custom Blade templates.

While you can set all this data directly in the [front matter](blog-posts#author), that quickly becomes tedious and hard to maintain.
Instead, you can predefine authors in the Hyde config. When writing posts, just specify the username in the front matter,
and the rest of the data will be pulled from a matching entry found in the configuration file.

#### Configuration

Authors are defined in the `config/hyde.php` file under the `authors` key. Each author is keyed by their username and is configured using the `Author::create()` method:

```php
// filepath: config/hyde.php
'authors' => [
    'mr_hyde' => Author::create(
        // The following fields, along with the username, are used by the default blog post templates.
        name: 'Mr. Hyde',
        website: 'https://hydephp.com',

        // These fields are not currently used in the default templates, but you can use them in your custom views.
        bio: 'The mysterious author of HydePHP',
        avatar: 'avatar.png',
        socials: [
            'twitter' => '@HydeFramework',
            'github' => 'hydephp',
        ],
    ),
],
```

This is equivalent to the following front matter in a blog post:

```yaml
author:
    username: mr_hyde
    name: Mr. Hyde
    website: https://hydephp.com
    bio: The mysterious author of HydePHP
    avatar: avatar.png
    socials:
        twitter: "@HydeFramework"
        github: hydephp
```

But you only have to specify the username to get all the other data.

```yaml
author: mr_hyde
```

If you want to override the data for a specific post, you can do so in the [front matter](blog-posts#author) which is great for guest authors or one-off posts.

#### Available Fields

- `name`: The author's display name (optional, generated from username if not provided)
- `website`: The author's website URL (optional)
- `bio`: A short biography (optional, not used in default templates)
- `avatar`: Path to the author's avatar image (optional, not used in default templates)
- `socials`: An array of social media links (optional, not used in default templates)

#### Notes

- Usernames are automatically normalized (converted to lowercase with spaces replaced by underscores)
- The `PostAuthor` class includes a `getPosts()` method to retrieve all posts by an author
- Authors can be accessed through `Hyde::authors()`

For more advanced usage and customization, refer to the [source code](https://github.com/hydephp/framework/blob/master/src/Framework/Features/Blogging/Models/PostAuthor.php) which is well documented.

### Footer

Most websites have a footer with copyright details and contact information. You probably want to change the Markdown to include your information, though you are of course welcome to keep the default attribution link!

The footer component is made up of a few levels of components, depending on how much you want to customize.

#### Customizing the Markdown text

There are two ways to customize the footer text. First, you can set it in the configuration file:

```php
// filepath: config/hyde.php
'footer' => 'Site proudly built with [HydePHP](https://github.com/hydephp/hyde) 🎩',
```

If you don't want to write Markdown in the configuration file, you can create a Markdown file in your includes directory. When this file is found, it will be used instead of the configuration setting.

```markdown
// filepath: resources/includes/footer.md
Site proudly built with [HydePHP](https://github.com/hydephp/hyde) 🎩
```

In both cases the parsed Markdown will be rendered in the footer Blade component.

#### Customizing the Blade component

The actual footer component is rendered using the [`layouts/footer.blade.php`](https://github.com/hydephp/framework/blob/master/resources/views/layouts/footer.blade.php) Blade template.

In this template we automatically render the configured footer Markdown text. If you want to change this behaviour, for example, HydePHP.com uses a more sophisticated footer, simply [publish the footer component](#blade-views).

#### Disabling the footer entirely

If you don't want to have a footer on your site, you can set the `'footer'` configuration option to `false`.

```php
// filepath: config/hyde.php
'footer' => 'false',
```

### Head and script HTML hooks

>info Note: The configuration options `head` and `scripts` were added in HydePHP v1.5. If you are running an older version, you need to use the Blade options, or upgrade your project.

While the most robust way to add custom HTML to the head or body of your site is to publish the Blade layouts, or pushing to the `meta` or `scripts` stacks,
you can also add custom HTML directly in the configuration file. This works especially well to quickly add things like analytics widgets or similar in the `hyde.yml` file, though the possibilities are endless.

To add custom HTML to your layouts, you can use the `head` and `scripts` configuration options in the `config/hyde.php` file (or the `hyde.yml` file).
The HTML will be added to the `<head>` section, or just before the closing `</body>` tag, respectively.
Note that the HTML is added to all pages. If you need to add HTML to a specific page, you will need to override the layout for that page.

```php
// filepath: config/hyde.php
'head' => '<!-- Custom HTML in the head -->',
'scripts' => '<!-- Custom HTML in the body -->',
```

```yaml
# filepath: hyde.yml

hyde:
  head: "<!-- Custom HTML in the head -->"
  scripts: "<!-- Custom HTML in the body -->"
```

You can of course also add multiple lines of HTML:

```php
// filepath: config/hyde.php
'head' => <<<HTML
    <!-- Custom HTML in the head -->
    <link rel="stylesheet" href="https://example.com/styles.css">
HTML,
```

```yaml
# filepath: hyde.yml

hyde:
  head: |
    <!-- Custom HTML in the head -->
    <link rel="stylesheet" href="https://example.com/styles.css">
```

### Navigation Menu & Sidebar

A great time-saving feature of HydePHP is the automatic navigation menu and documentation sidebar generation.
Hyde is designed to automatically configure these menus for you based on the content you have in your project.

Still, you will likely want to customize some parts of these menus, and thankfully, Hyde makes it easy to do so.

#### Customizing the navigation menu

- To customize the navigation menu, use the setting `navigation.order` in the `hyde.php` config.
- When customizing the navigation menu, you should use the [route key](core-concepts#route-keys) of the page.
- You can use either a basic list array or specify explicit priorities.

Learn more in the [Navigation Menu](navigation) documentation.

#### Customizing the documentation sidebar

- To customize the sidebar, use the setting `sidebar.order` in the `docs.php` config.
- When customizing the sidebar, you can use the route key, or just the [page identifier](core-concepts#page-identifiers) of the page.
- Similar to the navigation menu, you can use a basic list array or specify explicit priorities.
- You can also use front matter in individual documentation pages to customize their appearance and behavior in the sidebar.

Learn more in the [Documentation Pages](documentation-pages#sidebar) documentation.

## Additional Advanced Options

The following configuration options in the `config/hyde.php` file are intended for advanced users and
should only be modified if you fully understand their impact. The code examples show the default values.

### `media_extensions`

This option allows you to specify file extensions considered as media files, which will be copied to the output directory.
To add more extensions, either append them to the existing array or override the entire array.

```php
// filepath config/hyde.php
use \Hyde\Support\Filesystem\MediaFile;

'media_extensions' => array_merge([], MediaFile::EXTENSIONS),
```

### `safe_output_directories`

This setting defines a list of directories deemed safe to empty during the site build process as a safeguard to prevent accidental data loss.
If the site output directory is not in this list, the build command will prompt for confirmation before emptying it. It is preconfigured
with common directories including the default one, but you are free to change this to include any custom directories you may need.

```php
// filepath config/hyde.php
'safe_output_directories' => ['_site', 'docs', 'build'],
```

### `generate_build_manifest`

Determines whether a JSON build manifest with metadata about the build should be generated. Set to `true` to enable.

```php
// filepath config/hyde.php
'generate_build_manifest' => true,
```

### `build_manifest_path`

Specifies the path where the build manifest should be saved, relative to the project root.

```php
// filepath config/hyde.php
'build_manifest_path' => 'app/storage/framework/cache/build-manifest.json',
```

### `theme_toggle_buttons`

>info This feature was added in HydePHP v1.7.0

This setting allows you to enable or disable the theme toggle buttons in the navigation menu.

```php
// filepath config/hyde.php
'theme_toggle_buttons' => true,
```

If the `Feature::Darkmode` setting is disabled in the `features` array in the same file, this won't do anything, but if darkmode is enabled,
setting this setting to `false` will make so that the buttons will not show up in the app layout nor the documentation layout;
instead the appropriate color scheme will be automatically applied based on the browser system settings.

## Blade Views

Hyde uses the Laravel Blade templating engine. Most parts of the included templates have been extracted into components to be customized easily.
Before editing the views you should familiarize yourself with the [Laravel Blade Documentation](https://laravel.com/docs/10.x/blade).

To edit a default Hyde component you need to publish them first using the `hyde publish:views` command.

```bash
php hyde publish:views
```

The files will then be available in the `resources/views/vendor/hyde` directory.

>info **Tip:** If you use Linux/macOS or Windows with WSL you will be able to interactively select individual files to publish.

## Frontend Styles

Hyde is designed to not only serve as a framework but a whole starter kit and comes with a Tailwind starter template
for you to get up and running quickly. If you want to customize these, you are free to do so.
Please see the [Managing Assets](managing-assets) page to learn more.

## Markdown Configuration

Hyde uses [League CommonMark](https://commonmark.thephpleague.com/) for converting Markdown into HTML, and
uses the GitHub Flavored Markdown extension. The Markdown related settings are found in the `config/markdown.php` file.
Below follows an overview of the Markdown configuration options available in Hyde.

### CommonMark Extensions

You can add any extra [CommonMark Extensions](https://commonmark.thephpleague.com/2.3/extensions/overview/),
or change the default ones, using the `extensions` array in the config file. They will then automatically be loaded into
the CommonMark converter environment when being set up by Hyde.

```php
// filepath: config/markdown.php
'extensions' => [
    \League\CommonMark\Extension\GithubFlavoredMarkdownExtension::class,
    \League\CommonMark\Extension\Attributes\AttributesExtension::class,
],
```

Remember that you may need to install any third party extensions through Composer before you can use them.

### CommonMark Configuration

In the same file you can also change the configuration values to be passed to the CommonMark converter environment.
Hyde handles many of the options automatically, but you may want to override some of them and/or add your own.

```php
// filepath: config/markdown.php
'config' => [
    'disallowed_raw_html' => [
        'disallowed_tags' => [],
    ],
],
```

See the [CommonMark Configuration Docs](https://commonmark.thephpleague.com/2.3/configuration/) for the available options.
Any custom options will be merged with the defaults.

### Allow Raw HTML

Since Hyde uses [GitHub Flavored Markdown](https://commonmark.thephpleague.com/2.3/extensions/github-flavored-markdown/),
some HTML tags are stripped out by default. If you want to allow all arbitrary HTML tags, and understand the risks involved,
you can use the `allow_html` setting to enable all HTML tags.

```php
// filepath: config/markdown.php
'allow_html' => true,
```

### Allow Blade Code

HydePHP also allows you to use Blade code in your Markdown files. This is disabled by default, since it allows
arbitrary PHP code specified in Markdown to be executed. It's easy to enable however, using the `enable_blade` setting.

```php
// filepath: config/markdown.php
'enable_blade' => true,
```

See the [Blade in Markdown](advanced-markdown#blade-support) documentation for more information on how to use this feature.

## YAML Configuration

The settings in the `config/hyde.php` file can also be set by using a `hyde.yml` file in the root of your project directory.

Note that YAML settings cannot call any PHP functions, so you can't access helpers like `env()` for environment variables,
nor declare authors or navigation links, as you cannot use facades and objects. But that doesn't stop you from using both
files if you want to. Just keep in mind that any duplicate settings in the YAML file override any made in the PHP file.

Here is an example showing some of the `config/hyde.php` file settings, and how they would be set in the YAML file.

```yaml
# filepath hyde.yml

name: HydePHP
url: "http://localhost"
pretty_urls: false
generate_sitemap: true
rss:
  enabled: true
  filename: feed.xml
  description: HydePHP RSS Feed
language: en
output_directory: _site
```

### Namespaced YAML Configuration

If you are running `v1.2` or higher, you can also use namespaced configuration options in the YAML file.

This allows you to set the settings of **any** configuration file normally found in the `config` directory.

This feature is automatically enabled when you have a `hyde:` entry **first** in your `hyde.yml` file

```yaml
# filepath hyde.yml

hyde:
  name: HydePHP

docs:
  sidebar:
    header: "My Docs"
```

This would set the `name` setting in the `config/hyde.php` file, and the `sidebar.header` setting in the `config/docs.php` file.

Each top level key in the YAML file is treated as a namespace, and the settings are set in the corresponding configuration file.
You can of course use arrays like normal even in namespaced configuration.
