---
navigation:
    priority: 35
---

# Troubleshooting

Since Hyde has a lot of "magic" features which depend on some base assumptions, there might be some "gotchas" you might run into. Here are some common issues you might encounter. Did you find a new one? Send a PR to [update the docs](https://github.com/hydephp/docs)!

>info Tip: You can run `php hyde validate` to run a series of tests to help you catch common issues.

## General Tips

(In no particular order of importance)

1. In general, **Hyde is actually pretty forgiving**. While this article makes it sound like there are a lot of rules to follow,
   honestly don't worry about it. Hyde will attempt to fix mistakes and make your life easier.
2. You don't need to set an H1 heading in blog posts. The H1 is set by Hyde based on the front matter title.
3. You never need front matter, though it is often useful.
   For example, Hyde makes attempts to guess the title for a page depending on the content. (Headings, filenames, etc).

## Conventions to Follow

### File naming

For Hyde to be able to discover your files, you should follow the following conventions.

Markdown files should have the extension `.md`. Blade files should have the extension `.blade.php`.

Unexpected behaviour might occur if you use conflicting file names.
All the following filenames are resolved into the same destination file:
`foo-bar.md`, `Foo-Bar.md`, `foo-bar.blade.php`, causing only one of them to be saved.

Remember, files retain their base filenames when compiled to HTML.

#### Summary

- ✔ **Do** use lowercase filenames and extensions
- ✔ **Do** use filenames written in kebab-case-format
- ✔ **Do** use the proper file extensions
- ❌ **Don't** use conflicting source file names

## Extra Information

### Definitions

We will use the following definitions to describe the behaviour of Hyde.

#### General

- **Hyde**: The application that you are using.
- **HydeCLI**: The command-line interface for Hyde.
- **Framework**: The package containing the core codebase.

#### Path components

- **Identifier**: The filepath without the extension, relative to the page type source directory.
- **Route Key**: The page type's output directory plus the identifier. Example: `posts/hello-world`
- **Basename**: The filename without the extension. Example: `hello-world`
- **Filename**: The full name of a file with the extension. Example: `hello-world.md`
- **Filepath**: The full file path including extension. Example: `_posts/hello-world.md`

You can read more about some of these in the [Core Concepts](core-concepts#paths-identifiers-and-route-keys) article.

## Common Issues, Causes, and Solutions

| Issue                                              | Possible Cause / Issue Context                                                                                                                            | Possible Solution                                                                                                                                                                                 |
|----------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| 404 error when visiting site                       | Are you missing an index file in the `_pages` directory?                                                                                                  | Add an `index.md` or `index.blade.php` to the `_pages` directory                                                                                                                                  |
| Navigation menu not linking to the docs            | You probably don't have an `index.md` file in the `_docs` directory.                                                                                      | Create a `_docs/index.md` file                                                                                                                                                                    |
| Page not discovered when compiling                 | The file name may be invalid                                                                                                                              | Ensure you follow the correct file naming convention.                                                                                                                                             |
| Page compiles slowly                               | The Torchlight extension may cause the compile times to increase as API calls need to be made.                                                            | Try disabling Torchlight                                                                                                                                                                          |
| Torchlight not working                             | Missing Composer package, missing API token, extension disabled in the config.                                                                            | Reinstall Torchlight, add your token in the `.env` file, check config                                                                                                                             |
| Missing styles and/or assets                       | You may have accidentally deleted the files, or you have added new Tailwind classes.                                                                      | Run `npm run build`                                                                                                                                                                               |
| Image not found                                    | You may be using a bad relative path.                                                                                                                     | Ensure your relative paths are correct.  See [managing-assets](managing-assets#referencing-images).                                                                                               |
| Wrong layout used                                  | Hyde determines the layout template to use depending on the directory of the source file                                                                  | Ensure your source file is in the right directory.                                                                                                                                                |
| Invalid/no permalinks or post URIs                 | You may be missing or have an invalid site URL                                                                                                            | Set the site URL in the `.env` file                                                                                                                                                               |
| No styles in custom Blade pages                    | When using custom blade pages need to add the styles yourself. You can do this by extending the default layout                                            | Use the app layout, or by include the Blade components directly.                                                                                                                                  |
| Overriding Hyde views is not working               | Ensure the Blade views are in the correct directory.                                                                                                      | Rerun `php hyde publish:views`.                                                                                                                                                                   |
| Styles not updating when deploying site            | It could be a caching issue. To be honest, when dealing with styles, it's always a caching issue.                                                         | Clear your cache, and optionally complain to your site host                                                                                                                                       |
| Documentation sidebar items are in the wrong order | Double check the config, make sure the route keys are written correctly. Check that you are not overriding with front matter.                             | Check config for typos and front matter                                                                                                                                                           |
| Issues with date in blog post front matter         | The date is parsed by the PHP `strtotime()` function. The date may be in an invalid format, or the front matter is invalid                                | Ensure the date is in a format that `strtotime()` can parse. Wrap the front matter value in quotes.                                                                                               |
| RSS feed not being generated                       | The RSS feed requires that you have set a site URL in the Hyde config or the `.env` file. Also check that you have blog posts, and that they are enabled. | Check your configuration files.                                                                                                                                                                   |                                                                                                                                                         |
| Sitemap not being generated                        | The sitemap requires that you have set a site URL in the Hyde config or the `.env` file.                                                                  | Check your configuration files.                                                                                                                                                                   |                                                                                                                                                         |
| Unable to do literally anything                    | If everything is broken, you may be missing a Composer package or your configuration files could be messed up.                                            | Run `composer install` and/or `composer update`. If you can run HydeCLI commands, update your configs with `php hyde publish:configs`, or copy them manually from GitHub or the vendor directory. |
| Namespaced Yaml config (`hyde.yml`) not working    | When using namespaced Yaml configuration, you must begin the file with `hyde:`, even if you just want to use another file for example `docs:`.            | Make sure the file starts with `hyde:` (You don't need to specify any options, as long as it's present). See [`#1475`](https://github.com/hydephp/develop/issues/1475)                            |

### Extra troubleshooting information

#### Fixing a broken config

If your configuration is broken, you might not be able to run any commands through the HydeCLI.
To remedy this you can copy the config files from the vendor directory into the project directory.
You can do this manually, or with the following rescue command:

```bash
copy vendor/hyde/framework/config/hyde.php config/hyde.php
```
