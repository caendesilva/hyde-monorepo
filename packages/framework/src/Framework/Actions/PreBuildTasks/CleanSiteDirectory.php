<?php

declare(strict_types=1);

namespace Hyde\Framework\Actions\PreBuildTasks;

use Hyde\Hyde;
use Hyde\Facades\Config;
use Hyde\Facades\Filesystem;
use Hyde\Support\Filesystem\MediaFile;
use Hyde\Framework\Features\BuildTasks\PreBuildTask;

use function basename;
use function in_array;
use function sprintf;

class CleanSiteDirectory extends PreBuildTask
{
    protected static string $message = 'Removing all files from build directory';

    public function handle(): void
    {
        if ($this->isItSafeToCleanOutputDirectory()) {
            Filesystem::unlink(Filesystem::findFiles(Hyde::sitePath(), ['html', 'json'])->all());
            Filesystem::cleanDirectory(MediaFile::outputPath());
        }
    }

    public function printFinishMessage(): void
    {
        $this->newLine();
    }

    protected function isItSafeToCleanOutputDirectory(): bool
    {
        if (! $this->isOutputDirectoryWhitelisted() && ! $this->askIfUnsafeDirectoryShouldBeEmptied()) {
            $this->info('Output directory will not be emptied.');

            return false;
        }

        return true;
    }

    protected function isOutputDirectoryWhitelisted(): bool
    {
        return in_array(basename(Hyde::sitePath()), $this->safeOutputDirectories());
    }

    protected function askIfUnsafeDirectoryShouldBeEmptied(): bool
    {
        return $this->confirm(sprintf(
            'The configured output directory (%s) is potentially unsafe to empty. '.
            'Are you sure you want to continue?',
            Hyde::getOutputDirectory()
        ));
    }

    /** @return array<string> */
    protected function safeOutputDirectories(): array
    {
        /** @var array<string> $directories */
        $directories = Config::getArray('hyde.safe_output_directories', ['_site', 'docs', 'build']);

        return $directories;
    }
}
