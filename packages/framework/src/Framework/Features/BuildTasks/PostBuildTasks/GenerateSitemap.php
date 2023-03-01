<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\BuildTasks\PostBuildTasks;

use Hyde\Framework\Features\BuildTasks\BuildTask;
use Hyde\Framework\Features\BuildTasks\Contracts\RunsAfterBuild;
use Hyde\Framework\Features\XmlGenerators\SitemapGenerator;
use Hyde\Hyde;

class GenerateSitemap extends BuildTask implements RunsAfterBuild
{
    public static string $message = 'Generating sitemap';

    public function handle(): void
    {
        file_put_contents(
            Hyde::sitePath('sitemap.xml'),
            SitemapGenerator::make()
        );
    }

    public function printFinishMessage(): void
    {
        $this->createdSiteFile('_site/sitemap.xml')->withExecutionTime();
    }
}
