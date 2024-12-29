<?php

declare(strict_types=1);

namespace Hyde\Console\Commands;

use Hyde\Hyde;
use Hyde\Facades\Config;
use Hyde\Support\BuildWarnings;
use Hyde\Console\Concerns\Command;
use Hyde\Framework\Services\BuildService;
use Hyde\Framework\Services\BuildTaskService;
use Illuminate\Support\Facades\Process;

use function memory_get_peak_usage;
use function number_format;
use function array_search;
use function microtime;
use function sprintf;
use function app;

/**
 * Run the static site build process.
 */
class BuildSiteCommand extends Command
{
    /** @var string */
    protected $signature = 'build
        {--run-vite : Build frontend assets using Vite}
        {--run-prettier : Format the output using NPM Prettier}
        {--pretty-urls : Should links in output use pretty URLs?}
        {--no-api : Disable API calls, for example, Torchlight}
        {--run-dev : [Removed] Use --run-vite instead}
        {--run-prod : [Removed] Use --run-vite instead}';

    /** @var string */
    protected $description = 'Build the static site';

    protected BuildService $service;
    protected BuildTaskService $taskService;

    public function handle(): int
    {
        $this->checkForDeprecatedRunMixCommandUsage();

        $timeStart = microtime(true);

        $this->title('Building your static site!');

        $this->service = new BuildService($this->output);

        $this->configureBuildTaskService();

        $this->runPreBuildActions();

        $this->service->compileStaticPages();

        $this->runPostBuildActions();

        $this->printFinishMessage($timeStart);

        return $this->getExitCode();
    }

    protected function configureBuildTaskService(): void
    {
        /** @var BuildTaskService $taskService */
        $taskService = app(BuildTaskService::class);

        $this->taskService = $taskService;
        $this->taskService->setOutput($this->output);
    }

    protected function runPreBuildActions(): void
    {
        if ($this->option('no-api')) {
            $this->info('Disabling external API calls');
            $this->newLine();
            /** @var array<string, string> $config */
            $config = Config::getArray('hyde.features', []);
            unset($config[array_search('torchlight', $config)]);
            Config::set(['hyde.features' => $config]);
        }

        if ($this->option('pretty-urls')) {
            $this->info('Generating site with pretty URLs');
            $this->newLine();
            Config::set(['hyde.pretty_urls' => true]);
        }

        if ($this->option('run-vite')) {
            $this->runNodeCommand('npm run build', 'Building frontend assets for production!');
        }

        $this->taskService->runPreBuildTasks();
    }

    public function runPostBuildActions(): void
    {
        $this->taskService->runPostBuildTasks();

        if ($this->option('run-prettier')) {
            $this->runNodeCommand(
                'npx prettier '.Hyde::pathToRelative(Hyde::sitePath()).'/**/*.html --write --bracket-same-line',
                'Prettifying code!',
                'prettify code'
            );
        }
    }

    protected function printFinishMessage(float $timeStart): void
    {
        if ($this->hasWarnings()) {
            $this->newLine();
            $this->error('There were some warnings during the build process:');
            $this->newLine();
            BuildWarnings::writeWarningsToOutput($this->output, $this->output->isVerbose());
        }

        $executionTime = (microtime(true) - $timeStart);
        $this->info(sprintf(
            "\nAll done! Finished in %s seconds (%sms) with %sMB peak memory usage",
            number_format($executionTime, 2),
            number_format($executionTime * 1000, 2),
            number_format(memory_get_peak_usage() / 1024 / 1024, 2)
        ));

        $this->info('Congratulations! 🎉 Your static site has been built!');
        $this->line(
            'Your new homepage is stored here -> '.
            static::fileLink(Hyde::sitePath('index.html'))
        );
    }

    protected function runNodeCommand(string $command, string $message, ?string $actionMessage = null): void
    {
        $this->info($message.' This may take a second.');

        $output = Process::command($command)->run();

        $this->line($output->output() ?? sprintf(
            '<fg=red>Could not %s! Is NPM installed?</>',
            $actionMessage ?? 'run script'
        ));
    }

    protected function hasWarnings(): bool
    {
        return BuildWarnings::hasWarnings() && BuildWarnings::reportsWarnings();
    }

    protected function getExitCode(): int
    {
        if ($this->hasWarnings() && BuildWarnings::reportsWarningsAsExceptions()) {
            return Command::INVALID;
        }

        return Command::SUCCESS;
    }

    /**
     * This method is called when the removed --run-dev or --run-prod options are used.
     *
     * @deprecated Use --run-vite instead
     * @since v2.0 - This will be removed after 2-3 minor releases depending on the timeframe between them. (~v2.3)
     *
     * @codeCoverageIgnore
     */
    protected function checkForDeprecatedRunMixCommandUsage(): void
    {
        if ($this->option('run-dev') || $this->option('run-prod')) {
            $this->error('The --run-dev and --run-prod options have been removed in HydePHP v2.0.');
            $this->info('Please use --run-vite instead to build assets for production with Vite.');
            $this->line('See https://github.com/hydephp/develop/pull/2013 for more information.');

            exit(Command::INVALID);
        }
    }
}
