<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Feature\Commands;

use Hyde\Testing\TestCase;
use Illuminate\Support\ServiceProvider;

/**
 * @covers \Hyde\Console\Commands\VendorPublishCommand
 */
class VendorPublishCommandTest extends TestCase
{
    public function test_command_prompts_for_provider_or_tag()
    {
        ServiceProvider::$publishes = [
            'ExampleProvider' => '',
        ];
        ServiceProvider::$publishGroups = [
            'example-configs' => [],
        ];

        $this->artisan('vendor:publish')
            ->expectsChoice('Which provider or tag\'s files would you like to publish?', 'Tag: example-configs', [
                '<comment>Publish files from all providers and tags listed below</comment>',
                '<fg=gray>Provider:</> ExampleProvider',
                '<fg=gray>Tag:</> example-configs',
            ])
            ->assertExitCode(0);
    }
}
