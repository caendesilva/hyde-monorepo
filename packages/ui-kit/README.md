# HydePHP UI Kit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hyde/ui-kit.svg?style=flat-square)](https://packagist.org/packages/hyde/ui-kit)
[![Total Downloads](https://img.shields.io/packagist/dt/hyde/ui-kit.svg?style=flat-square)](https://packagist.org/packages/hyde/ui-kit)

## About

The HydePHP UI Kit is a collection of minimalistic and un-opinionated TailwindCSS components for Laravel Blade,
indented to be used with HydePHP. Note that these components may require CSS classes not present in the bundled app.css
file and that you may need to recompile the CSS file using the included Tailwind and Vite configuration.

## Installation

You can install the package via composer:

```bash
composer require hyde/ui-kit
```

Since HydePHP already comes with several built-in views and templates, including a precompiled CSS file to get you started quickly, this package is not required to use HydePHP and is tailored to intermediate-to-advanced users, giving you an excellent productivity boost in kick-starting development of any custom Blade pages.

## Usage

Once installed, the package service provider will automatically register the Blade components for you to use.

You can then use the components and layouts when crafting your custom Blade pages.
You might also need to recompile the CSS file using the Tailwind and Vite configuration included with Hyde.

You can see a list of all available components in the [documentation](https://hydephp.github.io/ui-kit/).

## Contributing

Contributions are welcome. Please see the [hydephp/develop](https://github.com/hydephp/develop/issues) monorepo for details.

### Security

If you discover any security related issues, please email caen@desilva.se instead of using the issue tracker.


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
