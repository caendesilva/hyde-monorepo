---
navigation:
    label: "Realtime Compiler"
    priority: 81
---

# Realtime Compiler

The Hyde Realtime Compiler is included with Hyde installations and is what powers the `php hyde serve` command,
allowing you to preview your static site on a local development server without having to rebuild the site.

### Usage

To start the server, run the following command from a terminal in your project directory:

```bash
php hyde serve
```

This will start a local development server at `http://localhost:8080`

>warning Please note that the server is designed for local development, and should not be used on a public network.

### Options

- `--host=`: <comment>[default: "localhost"]</comment>
- `--port=`: <comment>[default: 8080]</comment>
- `--save-preview=`: Should the served page be saved to disk? (Overrides config setting)
- `--dashboard=`: Enable the realtime compiler dashboard. (Overrides config setting)
- `--pretty-urls=`: Enable pretty URLs. (Overrides config setting)
- `--play-cdn=`: Enable the Tailwind Play CDN. (Overrides config setting)
- `--open=false`: Open the site preview in the browser.
- `--vite`: Enable Vite for Hot Module Replacement (HMR).

### Vite Integration

By adding the `--vite` option, the serve command will initiate Vite's development server alongside the Hyde Realtime Compiler. This setup enables Hot Module Replacement (HMR), allowing for instant updates to your site as you make changes to your assets.

### Configuration

The server can be configured in the `config/hyde.php` file to change the port, host, and to customize its features.

```php
// filepath config/hyde.php

'server' => [
    // The default port the preview is served on
    'port' => env('SERVER_PORT', 8080),

    // The default host the preview is served on
    'host' => env('SERVER_HOST', 'localhost'),

    // Should preview pages be saved to the output directory?
    'save_preview' => env('SERVER_SAVE_PREVIEW', false),

    // Should the live edit feature be enabled?
    'live_edit' => env('SERVER_LIVE_EDIT', true),
],
```

### Live dashboard

#### Usage

The realtime compiler comes with a live dashboard that you can access at `http://localhost:8080/dashboard`.

From here, you can visually interact with your site content, including creating new pages and posts.

The live dashboard is not saved to your static site, and is only available through the development server.

#### Configuration

The dashboard can be customized, and disabled, in the `config/hyde.php` file.

```php
// filepath config/hyde.php

'server' => [
    // Configure the realtime compiler dashboard
    'dashboard' => [
        // Should the realtime compiler dashboard be enabled?
        'enabled' => env('SERVER_DASHBOARD', true),

        // Can the dashboard make edits to the project file system?
        'interactive' => true,

        // Should the dashboard show tips?
        'tips' => true,
    ],
],
```

_The dashboard was added in Realtime Compiler v3.0.0 (March 2023), with interactive features added in v3.1.0 (October 2023)_

### Live edit

#### Usage

The live edit feature allows you to quickly edit Markdown-based pages (posts, docs, and pages) directly in the browser.

To enter the live editor, simply double-click on the article you want to edit, and it will be replaced with a text editor.
When you're done, click the save button to save the changes to the page's source file.

#### Shortcuts

The live editor supports the following keyboard shortcuts:
- `Ctrl + E` - Enter/Exit editor
- `Ctrl + S` - Save changes
- `esc` - Exit editor if active

#### Configuration

The live editor can be disabled in the `config/hyde.php` file.
The live editor plugin code will not be saved to your static site.

```php
// filepath config/hyde.php

'server' => [
    'live_edit' => env('SERVER_LIVE_EDIT', true),
],
```

### Source code

- **GitHub**: [hydephp/realtime-compiler](https://github.com/hydephp/realtime-compiler)
- **Packagist**: [hydephp/realtime-compiler](https://packagist.org/packages/hyde/realtime-compiler)
