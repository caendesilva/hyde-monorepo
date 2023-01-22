<?php

declare(strict_types=1);

namespace Hyde\Console\Commands;

use Exception;
use Hyde\Console\Concerns\Command as CommandAlias;
use Hyde\Foundation\Facades\PageCollection;
use Hyde\Framework\Services\BuildService;
use Hyde\Framework\Services\RebuildService;
use Hyde\Hyde;
use Hyde\Console\Concerns\Command;

/**
 * Hyde Command to build a single static site file.
 *
 * @see \Hyde\Framework\Testing\Feature\Commands\RebuildStaticSiteCommandTest
 */
class RebuildStaticSiteCommand extends Command
{
    /** @var string */
    protected $signature = 'rebuild
        {path : The relative file path (example: _posts/hello-world.md)}';

    /** @var string */
    protected $description = 'Run the static site builder for a single file';

    /**
     * The source path.
     */
    public string $path;

    public function handle(): int
    {
        $time_start = microtime(true);

        if ($this->argument('path') === '_media') {
            (new BuildService($this->getOutput()))->transferMediaAssets();

            return Command::SUCCESS;
        }

        $this->path = $this->sanitizePathString($this->argument('path'));

        try {
            $this->validate();
        } catch (Exception $exception) {
            return $this->handleException($exception);
        }

        (new RebuildService($this->path))->execute();

        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);

        $this->info(sprintf(
            'Created %s in %s seconds. (%sms)',
            CommandAlias::createClickableFilepath(PageCollection::getPage($this->path)->getOutputPath()),
            number_format(
                $execution_time,
                2
            ),
            number_format(($execution_time * 1000), 2)
        ));

        return Command::SUCCESS;
    }

    /**
     * Perform a basic sanitation to strip trailing characters.
     */
    public function sanitizePathString(string $path): string
    {
        return str_replace('\\', '/', trim($path, '.\\/'));
    }

    /**
     * Validate the path to catch common errors.
     *
     * @throws Exception
     */
    public function validate(): void
    {
        if (! (
            str_starts_with($this->path, Hyde::pathToRelative(Hyde::getBladePagePath())) ||
            str_starts_with($this->path, Hyde::pathToRelative(Hyde::getMarkdownPagePath())) ||
            str_starts_with($this->path, Hyde::pathToRelative(Hyde::getMarkdownPostPath())) ||
            str_starts_with($this->path, Hyde::pathToRelative(Hyde::getDocumentationPagePath()))
        )) {
            throw new Exception("Path [$this->path] is not in a valid source directory.", 400);
        }

        if (! file_exists(Hyde::path($this->path))) {
            throw new Exception("File [$this->path] not found.", 404);
        }
    }

    /**
     * Output the contents of an exception.
     *
     * @return int Error code
     */
    public function handleException(Exception $exception): int
    {
        $this->error('Something went wrong!');
        $this->warn($exception->getMessage());

        return (int) $exception->getCode();
    }
}
