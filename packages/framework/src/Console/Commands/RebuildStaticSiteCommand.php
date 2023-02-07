<?php

declare(strict_types=1);

namespace Hyde\Console\Commands;

use Exception;
use Hyde\Console\Concerns\Command;
use Hyde\Foundation\Facades\PageCollection;
use Hyde\Framework\Concerns\TracksExecutionTime;
use Hyde\Framework\Features\BuildTasks\BuildTask;
use Hyde\Framework\Services\BuildService;
use Hyde\Framework\Services\RebuildService;
use Hyde\Hyde;
use Illuminate\Console\OutputStyle;

/**
 * Hyde Command to build a single static site file.
 *
 * @see \Hyde\Framework\Testing\Feature\Commands\RebuildStaticSiteCommandTest
 *
 * @todo Refactor to use newer helpers
 * @todo Rename to RebuildStaticPageCommand since it only rebuilds a single page?
 */
class RebuildStaticSiteCommand extends Command
{
    use TracksExecutionTime;

    /** @var string */
    protected $signature = 'rebuild
        {path : The relative file path (example: _posts/hello-world.md)}';

    /** @var string */
    protected $description = 'Run the static site builder for a single file';

    /**
     * The source path.
     */
    protected string $path;

    public function handle(): int
    {
        $this->startClock();

        if ($this->argument('path') === Hyde::getMediaDirectory()) {
            (new BuildService($this->getOutput()))->transferMediaAssets();

            return Command::SUCCESS;
        }

        $this->path = $this->sanitizePathString($this->argument('path'));

        try {
            $this->validate();
        } catch (Exception $exception) {
            return $this->withException($exception);
        }

        return $this->makeBuildTask($this->output, $this->path)->handle() ?? Command::SUCCESS;
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
    public function withException(Exception $exception): int
    {
        $this->error('Something went wrong!');
        $this->warn($exception->getMessage());

        return (int) $exception->getCode();
    }

    protected function makeBuildTask(OutputStyle $output, string $path): BuildTask
    {
        return new class($output, $path) extends BuildTask
        {
            public static string $description = 'Rebuilding page';
            protected string $path;

            public function __construct(OutputStyle $output, string $path)
            {
                parent::__construct($output);
                $this->path = $path;
            }

            public function run(): void
            {
                (new RebuildService($this->path))->execute();
            }

            public function then(): void
            {
                $this->createdSiteFile(Command::createClickableFilepath(
                    PageCollection::getPage($this->path)->getOutputPath()
                ))->withExecutionTime();
            }
        };
    }
}
