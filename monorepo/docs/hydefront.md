# Internal HydeFront documentation

## Creating a new HydeFront version

### Prerequisites

- Make sure you have a Git submodule set up in `packages/hydefront` that points to the HydeFront repository.
- Make sure you have authorization to publish the package to NPM and push to the HydeFront repository.
- **Make sure that you have linked the local HydeFront package to the monorepo with `npm link`.**

If you need to set up the "submodule", run the following commands:

```bash
cd packages/hydefront
git init
git remote add origin git@github.com:hydephp/hydefront.git
git fetch
git checkout master --force
```

If you need to set up the NPM link, run the following commands:

```bash
cd packages/hydefront
npm link

cd ../../
npm link hydefront
```

### Setup

```bash
cd packages/hydefront
git pull origin master
```

### Version and publish

Head back to the monorepo root and run the following command to bump the version of the HydeFront package:

```bash
php packages/hydefront/.github/scripts/version.php patch|minor|major
```

This will create commits in both the monorepo and submodule. Now follow the following steps:

1. Verify that both commits are correct.
2. Build the new `app.css` through the monorepo
    - [ ] `npm run build`
3. Copy the compiled file to the HydeFront repository so it can be served from the CDN.
    - [ ] `cp _media/app.css packages/hydefront/dist/app.css`
4. Amend the HydeFront commit with the new `app.css` file.
    - [ ] `cd packages/hydefront && git add dist/app.css && git commit --amend --no-edit`
5. Push the submodule commit to the HydeFront repository.
    - [ ] `cd packages/hydefront && git push origin master`
6. Create the release on GitHub. Make sure to use the same version number as the one you just bumped.
    - [ ] Open https://github.com/hydephp/hydefront/releases/new
7. Refetch the submodule origin to get the new tag created by the release.
    - [ ] `cd packages/hydefront && git fetch origin`
8. Publish the package to NPM. (In the future, this could be automated with a GitHub action from the release.)
   - [ ] `npm publish`
9. Update the monorepo to use the new version.
    - [ ] `npm update hydefront`
    - **Note:** On major/minor version bumps, **remember to update** the `package.json` in the `packages/hyde` directory to use the new version!
    - **Note:** On major version bumps, **remember to update the HydeFront version in the Asset Facade!**
10. Amend the monorepo commit with the updated files (package-lock.json, _media/app.css, packages\hydefront, packages\hyde\package.json)
    - [ ] `git add packages/hydefront && git add packages/hyde/package.json && git add package-lock.json && git add _media/app.css && git add packages/framework/src/Facades/Asset.php && git commit --amend --no-edit`
