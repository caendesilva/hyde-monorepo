<?php

declare(strict_types=1);

namespace Hyde\Console\Commands;

use Hyde\Foundation\Facades\Pages;
use Hyde\Framework\Actions\StaticPageBuilder;
use LaravelZero\Framework\Commands\Command;
use Hyde\Framework\Features\Documentation\DocumentationSearchPage;
use Hyde\Framework\Features\Documentation\DocumentationSearchIndex;

/**
 * Run the build process for the documentation search index.
 */
class BuildSearchCommand extends Command
{
    /** @var string */
    protected $signature = 'build:search';

    /** @var string */
    protected $description = 'Generate the documentation search index';

    public function handle(): int
    {
        StaticPageBuilder::handle(Pages::get('docs/search.json') ?? new DocumentationSearchIndex());

        if (DocumentationSearchPage::enabled()) {
            StaticPageBuilder::handle(Pages::get('docs/search') ?? new DocumentationSearchPage());
        }

        return Command::SUCCESS;
    }
}
