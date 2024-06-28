<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature;

use Hyde\Testing\TestCase;

/**
 * High level test of the sitemap generation feature.
 *
 * @see \Hyde\Framework\Testing\Feature\Services\SitemapServiceTest
 * @see \Hyde\Framework\Testing\Feature\Commands\BuildSitemapCommandTest
 *
 * @covers \Hyde\Framework\Features\XmlGenerators\SitemapGenerator
 * @covers \Hyde\Framework\Actions\PostBuildTasks\GenerateSitemap
 * @covers \Hyde\Console\Commands\BuildSitemapCommand
 */
class SitemapFeatureTest extends TestCase
{
    public function testTheSitemapFeature()
    {
        //
    }
}
