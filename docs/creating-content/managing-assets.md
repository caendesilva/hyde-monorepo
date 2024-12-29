---
navigation:
    priority: 20
---

# Managing and Compiling Assets

## Introduction

Managing and compiling assets is a very common task in web development. Unfortunately, it's rarely fun.

With Hyde, **you don't have to do it**, in fact, you can skip this entire page if you are happy with how it is. But as always with Hyde, you can customize everything if you want to.

Hyde ships with a complete frontend using Blade views, TailwindCSS styles, and Alpine.js interactions, all pre-installed and bundled in the pre-configured Tailwind and Vite setup.

To get you started quickly, all the styles are already compiled and minified into `_media/app.css`, which will be copied to the `_site/media/app.css` directory when you run `php hyde build`.

## Vite

Hyde uses [Vite](https://vitejs.dev/) to compile assets. Vite is a build tool that aims to provide a faster and more efficient development experience for modern web projects.

### Why Vite?

HydePHP integrates Vite to compile assets such as CSS and JavaScript files. This integration ensures that your assets are processed efficiently, enhancing the development workflow by leveraging Vite's rapid build system.

#### Asset Management

**Development and Production Modes**

- **Development Mode**: Use `npm run dev` to start the Vite development HMR server, which provides fast live reloading and efficient compilation during development.
- **Production Mode**: Use `npm run build` for creating optimized, minified asset bundles ready for production deployment.

**Asset Compilation**:

- Assets are compiled from the `resources/assets` directory. The primary CSS file, `app.css`, is processed with TailwindCSS and other specified tools like PostCSS.
- Vite automatically processes all scripts and styles, outputting compiled files to the `_media` directory. These are copied to `_site/media` when the static site is built with `php hyde build`.

>warn Note that the HydePHP Vite integration only supports CSS and JavaScript files, if you try to load other file types, they will not be processed by Vite.

**Configuration**:
- You can customize Vite's behavior and output paths by modifying the pre-configured `vite.config.js` file in the project root directory.

### Hot Module Replacement (HMR)

Vite's HMR feature allows for instant updates to the browser without requiring a full page reload. This **only works** through the realtime compiler when the Vite development server is also running.

You can start both of these by running `npm run dev` and `php hyde serve` in separate terminals, or using the `--vite` flag with the serve command:

```bash
php hyde serve --vite
```

### Blade Integration

Hyde effortlessly integrates Vite with Blade views, allowing you to include compiled assets in your templates. The Blade components `hyde::layouts.styles` and `hyde::layouts.scripts` are already set up to load the compiled CSS and JavaScript files.

You can check if the Vite HMR server is running with `Vite::running()`, and you can include CSS and JavaScript resources with `Vite::asset('path')`, or `Vite::assets([])` to supply an array of paths.

**Example: Using Vite if the HMR server is enabled, or loading the compiled CSS file if not:**

```blade
@if(Vite::running())
    {{ Vite::assets(['resources/assets/app.css']) }}
@else
    <link rel="stylesheet" href="{{ asset('media/app.css') }}">
@endif
```

## Additional Information and Answers to Common Questions

### Is NodeJS/NPM Required for Using Hyde?

No, it is optional. All the compiled styles that you need are already installed, and NPM is only necessary if you want to compile your own styles.

### When Should Assets be Compiled?

The `_media/app.css` file that comes with Hyde contains TailwindCSS for all classes that are used in the default Blade views, as well as the HydeFront component styles.
If you want to customize the Tailwind settings or add custom styles, you will need to recompile the styles yourself.

For example, if you customize the Blade views and add new classes or add new classes in Blade-based pages, you may need to compile the assets yourself to get the new styles.
If you use Markdown-based pages, you do not need to compile anything as those styles are already included in the compiled CSS file.

## How are assets stored and managed?

The frontend assets are separated into three places.

- The `resources/assets` folder contain **source** files, meaning files that will be compiled into something else.
Here you will find the `app.css` file that bootstraps the TailwindCSS styles. This file is also an excellent place
to add your custom styles. It is also where we import HydeFront. If you compile this file in the base install,
it will output the same file that's already included in Hyde.

- The `_media` folder contains **compiled** (and usually minified) files. When Hyde compiles your static site,
all asset files here will get copied as they are into the `_site/media` folder.

- The `_site/media` folder contains the files that are served to the user.

### What is the difference between `_media` and `_site/media`?

It may seem weird to have two folders for storing the compiled assets, but it is quite useful.

The `_site` directory is intended to be excluded from version control, while the `_media` folder is included in the
version control. You are of course free to modify this behaviour by editing the `vite.config.js` file to change the output directory.

## How Do I Compile Assets?

First, make sure that you have installed all the NodeJS dependencies using `npm install`.
Then run `npm run dev` to compile the assets in development mode. For production builds, run `npm run build`.

### How does it work?

Hyde uses [Vite](https://vite.dev/) to compile assets.

When running the `npm run dev/prod` command, Vite will compile the `resources/assets/app.css` file into `_media/app.css` using PostCSS with TailwindCSS and AutoPrefixer.

The compiled assets will then be automatically copied to `_site/media` when you run `php hyde build`.

## Telling Hyde where to find assets

### Customizing the Blade templates

To make it really easy to customize asset loading, the styles and scripts are loaded in dedicated Blade components.

- Styles are loaded in `hyde::layouts.styles`
- Scripts are loaded in `hyde::layouts.scripts`

To customize them, run the following command:

```bash
php hyde publish:views layouts
```

Then edit the files found in `resources/views/vendor/hyde/layouts` directory of your project.

### You might not even need to do anything!

For the absolute majority of the cases, you don't need to mess with these files. Hyde will automatically load the app.css file when it exists in the `_media` directory.

#### Loading from CDN

If you want to load the same pre-compiled file included with Hyde but from a CDN, you can set `load_app_styles_from_cdn` to `true` in the `config/hyde.php` file. While you lose the ability to customize it, your styles will be automatically updated when needed, as the installed Framework version will automatically specify the correct version to load.

### Using the TailwindCSS Play CDN

>warning Note that the Play CDN is not meant for production use, so enabling it will add a warning to the web console.

If you want to use the [TailwindCSS Play CDN](https://tailwindcss.com/docs/installation/play-cdn), all you need to do is
set `use_play_cdn` to `true` in the `config/hyde.php` file. This will in addition to loading the standard `app.css` file,
also add a script tag which loads the TailwindCSS Play CDN.

What's even better is that Hyde will also inject the contents of the included `tailwind.config.js` file into the script tag,
so the Play CDN styles match the ones created by Vite.

All in all, this allows you to tinker around with Tailwind without having to compile anything.

## Managing Images

As mentioned above, assets stored in the _media folder are automatically copied to the _site/media folder,
making it the recommended place to store images. You can then easily reference them in your Markdown files.

### Referencing images

The recommended way to reference images is with relative paths as this offers the most compatibility,
allowing you to browse the site both locally on your filesystem and on the web when serving from a subdirectory.

>warning Note: The path is relative to the <b>compiled</b> file in the site output

The path to use depends on the location of the page. Note the subtle difference in the path prefix.

- If you are in a **Blog Post or Documentation Page**, use `../media/image.png`
- If in a **Markdown Page or Blade Page**, use `media/image.png`
- While not recommended, you can also use absolute paths: `/media/image.png`
- You can of course also use full URLs, for example when using a CDN.

#### Making images accessible

To improve accessibility, you should always add an `alt` text. Here is a full example including an image in a blog post:

```markdown
![Image Alt](../media/image.png "Image Title") # Note the relative path
```

### Setting a featured image for blog posts

Hyde offers great support for creating data-rich and accessible featured images for blog posts.

You can read more about this on the [creating blog posts page](blog-posts#image).
