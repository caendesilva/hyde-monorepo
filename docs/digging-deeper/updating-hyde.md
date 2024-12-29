---
navigation:
    priority: 35
---

# Updating Hyde Projects

This guide will help you update your HydePHP project to the latest version.

## Before You Start

When updating an existing installation, first ensure that you have a backup of your project in case anything goes wrong.
The recommended way to do this is to use Git as that allows you to smoothly roll back any changes.

## Version Compatibility

HydePHP follows [semantic versioning](https://semver.org/), so you can expect that minor and patch releases will be backwards compatible.
Only major releases may introduce breaking changes, all of which are thoroughly documented in the accompanying release notes.

New features and bug fixes are added in minor and patch releases, so it is recommended to keep your project up to date.

### Side effects and ensuring a smooth update

Please note that due to the intricate nature of software, there is a possibility that an update contains side effects,
hence why version controlling your site is helpful when updating versions as you can roll back changes. It can also
be helpful to version control the compiled HTML, so you can view a diff of the changes. Be sure to test that your site
can be built and that it looks as expected after updating before deploying the changes to your live site.

We of course have extensive tests in place run on each single code commit to ensure all code is functional, however,
it is still possible that some edge cases slip through. This means that a bug fix may impact an edge case that you depend on.

Obligatory related XKCD: [https://xkcd.com/1172](https://xkcd.com/1172)

### Before you start

Before you perform an update, please make sure you have a backup of your project.
Using Git is highly recommended as it allows you to easily roll back changes if something goes wrong.

## Update to a Major Version

When updating to a major version, you should read the release notes and the upgrade guide for that version.
If you are updating multiple major versions at once, it's recommended to update one major version at a time,
following the upgrade guide for each version. After following the upgrade guide, you can follow the post-update instructions below.

## Updating to a minor or patch version

Updating a minor or patch version is easy using Composer. Just run the following command:

```bash
composer update hyde/* --with-dependencies
```

Note that if you have hardcoded a version constraint in your `composer.json` file, you may need to update it manually.
You can always refer to the `composer.json` file in the HydePHP repository if you need a reference.

## Alternate Update Methods

### Updating using Git (advanced)

If you are using Git, you can set the `hydephp/hyde` repository as a Git remote and merge in the changes that way:

```bash
git remote add hyde https://github.com/hydephp/hyde.git
git fetch hyde
git merge hyde/master # OR: Replace 'master' with the version tag you want to update to

# Take care of any merge conflicts that arise, then install the updated dependencies

composer update
```

### Hard update using release archive

An alternate way to update your project is to essentially do a hard reset. This is only recommended if you haven't done many modifications to the HydePHP files.

Essentially: Download the [latest release](https://github.com/hydephp/hyde/releases/latest) from GitHub, extract it to a new project directory, then copy over your source files and install the dependencies.

## Post-update instructions

After updating Hyde you should update your config and resource files. This is where things can get a tiny bit dangerous
as existing files may be overwritten. If you are using Git, you can easily take care of any merge conflicts that arise.

First, ensure that your dependencies are up to date. If you have already done this, you can skip this step.

```bash
composer update
```

Then, update your config files. This is the hardest part, as you may need to manually copy in your own changes.

```bash
php hyde publish:configs
```

If you have published any of the included Blade components you will need to re-publish them.

```bash
php hyde publish:views layouts
php hyde publish:views components
```

You may also want to download any resources that have been updated. You download these from the Zip file of the latest release on GitHub.

The latest release can always be found at https://github.com/hydephp/hyde/releases/latest, where you can download the source code as a `zip` file under the "Assets" section.

Here are the paths you may be interested in copying over: (Using Git will help a ton here as it can show you diffs of changed files, and allow you to easily merge own changes)

```
├── app
│   ├── Providers
│   │   └── AppServiceProvider.php
│   ├── bootstrap.php
│   ├── config.php
│   └── storage/
├── composer.json
├── package.json
├── resources/
├── tailwind.config.js
└── vite.config.js
```

Next, recompile your assets, if you are not using the built-in assets.

```bash
npm install
npm run dev/prod
```

Finally, you can rebuild your site.

```bash
php hyde build
```

Now, you should browse your site files to ensure things are still looking as expected.
