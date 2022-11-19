<?php

declare(strict_types=1);

namespace Hyde\Console\Commands;

use Hyde\Console\Commands\Interfaces\CommandHandleInterface;
use Hyde\Facades\Features;
use Hyde\Framework\Features\BuildTasks\PostBuildTasks\GenerateRssFeed;
use Hyde\Framework\Features\BuildTasks\PostBuildTasks\GenerateSearch;
use Hyde\Framework\Features\BuildTasks\PostBuildTasks\GenerateSitemap;
use Hyde\Framework\Features\Publications\Models\PublicationType;
use Hyde\Framework\Services\BuildService;
use Hyde\Framework\Services\BuildTaskService;
use Hyde\Framework\Services\DiscoveryService;
use Hyde\Hyde;
use Hyde\Pages\MarkdownPage;
use Hyde\PublicationHelper;
use Illuminate\Support\Facades\Config;
use LaravelZero\Framework\Commands\Command;
use Rgasch\Collection\Collection;

/**
 * Hyde Command to run the Build Process.
 *
 * @see \Hyde\Framework\Testing\Feature\StaticSiteServiceTest
 */
class BuildPublicationsCommand extends Command implements CommandHandleInterface
{
    /** @var string */
    protected $signature = 'build:publications
        {--run-dev : Run the NPM dev script after build}
        {--run-prod : Run the NPM prod script after build}
        {--run-prettier : Format the output using NPM Prettier}
        {--pretty-urls : Should links in output use pretty URLs?}
        {--no-api : Disable API calls, for example, Torchlight}';

    /** @var string */
    protected $description = 'Build the PubType site';

    protected BuildService $service;

    public function handle(): int
    {
        $time_start = microtime(true);

        $this->title('Building your static site!');

        $this->service = new BuildService($this->output);

        $this->runPreBuildActions();

        $this->service->cleanOutputDirectory();

        $this->service->transferMediaAssets();

        // FIXME: refactor
        $this->build();

        $this->runPostBuildActions();

        $this->printFinishMessage($time_start);

        $this->output->writeln('Max memory used: '.memory_get_peak_usage() / 1024 / 1024 .' MB');

        return Command::SUCCESS;
    }

    // Warning: This is extremely hacky ...
    protected function build(): void
    {
        $pubTypes = PublicationHelper::getPublicationTypes();
        foreach ($pubTypes as $dir => $pubType) {
            $targetDirectory = "_site/$dir";
            @mkdir($targetDirectory);
            $publications = PublicationHelper::getPublicationsForPubType($pubType);
            $this->info("Building [$pubType->name] into [$targetDirectory] ...");
            $this->buildDetailPages($targetDirectory, $pubType, $publications);
            $this->buildListPage($targetDirectory, $pubType, $publications);
        }
    }

    // TODO: Is detail page the right name?
    protected function buildDetailPages(string $targetDirectory, PublicationType $pubType, Collection $publications): void
    {
        $template = $pubType->detailTemplate;

        // Mock a page
        $page = new MarkdownPage($template);
        view()->share('page', $page);
        view()->share('currentPage', $template);
        view()->share('currentRoute', $page->getRoute());

        // TODO this should not be in the hyde namespace as user is expected to implement this right?
        $detailTemplate = 'hyde::pubtypes.'.$template;
        foreach ($publications as $publication) {
            $slug = $publication->matter->__slug;
            $this->info("  Building [$slug] ...");
            $html = view('hyde::layouts.pubtype')->with(['component' => $detailTemplate, 'publication' => $publication])->render();
            file_put_contents("$targetDirectory/{$slug}.html", $html);
        }
    }

    // TODO: Move to post build task?
    protected function buildListPage(string $targetDirectory, PublicationType $pubType, Collection $publications): void
    {
        $template = 'hyde::pubtypes.'.$pubType->listTemplate;
        $this->info('  Building list page ...');
        $html = view($template)->with('publications', $publications)->render();
        file_put_contents("$targetDirectory/index.html", $html);
    }

    protected function runPreBuildActions(): void
    {
        if ($this->option('no-api')) {
            $this->info('Disabling external API calls');
            $this->newLine();
            $config = config('hyde.features');
            unset($config[array_search('torchlight', $config)]);
            Config::set(['hyde.features' => $config]);
        }

        if ($this->option('pretty-urls')) {
            $this->info('Generating site with pretty URLs');
            $this->newLine();
            Config::set(['site.pretty_urls' => true]);
        }
    }

    public function runPostBuildActions(): void
    {
        $service = new BuildTaskService($this->output);

        if ($this->option('run-prettier')) {
            $this->runNodeCommand(
                'npx prettier '.Hyde::pathToRelative(Hyde::sitePath()).'/**/*.html --write --bracket-same-line',
                'Prettifying code!',
                'prettify code'
            );
        }

        if ($this->option('run-dev')) {
            $this->runNodeCommand('npm run dev', 'Building frontend assets for development!');
        }

        if ($this->option('run-prod')) {
            $this->runNodeCommand('npm run prod', 'Building frontend assets for production!');
        }

        $service->runIf(GenerateSitemap::class, $this->canGenerateSitemap());
        $service->runIf(GenerateRssFeed::class, $this->canGenerateFeed());
        $service->runIf(GenerateSearch::class, $this->canGenerateSearch());

        $service->runPostBuildTasks();
    }

    protected function printFinishMessage(float $time_start): void
    {
        $execution_time = (microtime(true) - $time_start);
        $this->info(
            sprintf(
                'All done! Finished in %s seconds. (%sms)',
                number_format($execution_time, 2),
                number_format($execution_time * 1000, 2)
            )
        );

        $this->info('Congratulations! 🎉 Your static site has been built!');
        $this->line(
            'Your new homepage is stored here -> '.
            DiscoveryService::createClickableFilepath(Hyde::sitePath('index.html'))
        );
    }

    protected function runNodeCommand(string $command, string $message, ?string $actionMessage = null): void
    {
        $this->info($message.' This may take a second.');

        $output = shell_exec(
            sprintf(
                '%s%s',
                app()->environment() === 'testing' ? 'echo ' : '',
                $command
            )
        );

        $this->line(
            $output ?? sprintf(
            '<fg=red>Could not %s! Is NPM installed?</>',
            $actionMessage ?? 'run script'
        )
        );
    }

    protected function canGenerateSitemap(): bool
    {
        return Features::sitemap();
    }

    protected function canGenerateFeed(): bool
    {
        return Features::rss();
    }

    protected function canGenerateSearch(): bool
    {
        return Features::hasDocumentationSearch();
    }
}
