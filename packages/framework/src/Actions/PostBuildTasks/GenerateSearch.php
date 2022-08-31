<?php

namespace Hyde\Framework\Actions\PostBuildTasks;

use Hyde\Framework\Actions\GeneratesDocumentationSearchIndexFile;
use Hyde\Framework\Concerns\InteractsWithDirectories;
use Hyde\Framework\Contracts\AbstractBuildTask;
use Hyde\Framework\Services\DiscoveryService;

class GenerateSearch extends AbstractBuildTask
{
    use InteractsWithDirectories;

    public static string $description = 'Generating search index';

    public function run(): void
    {
        $expected = $this->guesstimateGenerationTime();
        if ($expected > 1) {
            $this->line("<fg=gray> > This will take an estimated $expected seconds. Terminal may seem non-responsive.</>");
        }

        GeneratesDocumentationSearchIndexFile::run();
    }

    public function then(): void
    {
        $this->writeln(sprintf("\n > Created <info>%s</info> in %s",
            GeneratesDocumentationSearchIndexFile::$filePath,
            $this->getExecutionTime()
        ));
    }

    /** @internal Estimated processing time per file in ms */
    public static float $guesstimationFactor = 52.5;

    protected function guesstimateGenerationTime(): int|float
    {
        return (int) round(count(DiscoveryService::getDocumentationPageFiles()) * static::$guesstimationFactor) / 1000;
    }
}
