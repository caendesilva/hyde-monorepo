<?php

declare(strict_types=1);

require_once __DIR__ . '/minima.php';

exit(main(function (): int {
    if (! is_dir(getcwd() . '/packages')) {
        $this->error('This script must be run from the root of the monorepo');
        $this->warning('Current working directory: ' . getcwd());
        return 1;
    }

    $baseDir = getcwd().'/packages/hydefront';

    global $argv;
    $versionType = $argv[1] ?? null;
    if ($versionType === null) {
        $this->error('Missing version type (supply as first argument)');
        return 1;
    }
    /** @noinspection SpellCheckingInspection */
    $nodeJsVersions = ['major', 'minor', 'patch', 'premajor', 'preminor', 'prepatch', 'prerelease'];
    if (! in_array($versionType, $nodeJsVersions)) {
        $this->error('Invalid version type: ' . $versionType);
        $this->warning('Must be one of: '.implode(', ', $nodeJsVersions));
        return 1;
    }

    // Ensure the packages/hydefront Git submodule is up-to-date with the origin
    $this->info('Checking if the HydeFront submodule is up-to-date...');
    $status = performRemoteVersionCheck($baseDir);
    if ($status !== 'up-to-date') {
        return 1;
    }

    FileBackupHelper::backup(
        $baseDir.'/package.json',
        $baseDir.'/package-lock.json',
    );

    $this->info("Creating a new HydeFront $versionType version...");
    $version = trim(shell_exec('cd packages/hydefront && npm version ' . $versionType . ' --no-git-tag-version'));
    $this->line("Updated package.json version to $version");

    $this->info('Updating version in dist files...');
    $this->line('---');
    passthru('php packages/hydefront/.github/scripts/post-build.php --skip-root-version-check', $fixExitCode);
    if ($fixExitCode !== 0) {
        $this->error('Failed to update version in dist files');
        FileBackupHelper::restore();
        return $fixExitCode;
    }
    FileBackupHelper::clear();
    $this->line('---');

    $this->info('Committing changes in monorepo...');
    passthru('git add packages/hydefront && git commit -m "Create HydeFront ' . $version . '"');

    $this->info('Committing changes in HydeFront...');
    passthru('cd packages/hydefront && git add . && git commit -m "HydeFront ' . $version . '"');

    $this->info('All done!');
    $this->warning("Don't forget to verify the changes, tag them, push them, then publish the NPM package and create the release on GitHub!");

    if (! str_contains($versionType, 'patch')) {
        $this->warning('This is not a patch version, so you should also update the version in the monorepo package.json');
    }

    return 0;
}));

class FileBackupHelper
{
    protected static array $backups = [];

    public static function backup(string ...$paths): void
    {
        foreach ($paths as $path) {
            $backupPath = $path.'.bak';
            copy($path, $backupPath);
            self::$backups[$path] = $backupPath;
        }
    }

    public static function restore(): void
    {
        foreach (self::$backups as $path => $backupPath) {
            copy($backupPath, $path);
            unlink($backupPath);
        }
    }

    public static function clear(): void
    {
        foreach (self::$backups as $backupPath) {
            unlink($backupPath);
        }
    }
}

function performRemoteVersionCheck(string $submodulePath): string
{
    // Navigate to the submodule directory and fetch the latest changes from origin
    shell_exec("cd $submodulePath && git fetch");

    // Get the status of the local branch compared to the origin
    $localStatus = trim(shell_exec("cd $submodulePath && git rev-parse @"));
    $remoteStatus = trim(shell_exec("cd $submodulePath && git rev-parse @{u}"));
    $baseStatus = trim(shell_exec("cd $submodulePath && git merge-base @ @{u}"));

    // Check if local repository is up-to-date
    if ($localStatus === $remoteStatus) {
        echo "The local repository is up-to-date with the origin.\n";
        return 'up-to-date';
    } elseif ($localStatus === $baseStatus) {
        echo "The local repository is behind the origin. You need to pull the changes before proceeding.\n";
    } elseif ($remoteStatus === $baseStatus) {
        echo "The local repository is ahead of the origin. You need to push the changes before proceeding.\n";
    } else {
        echo "The local repository has diverged from the origin. You need to resolve the conflicts before proceeding.\n";
    }

    return 'diverged';
}
