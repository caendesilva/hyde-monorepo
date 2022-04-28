<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;

class DebugCommandTest extends TestCase
{

    public function test_debug_command_can_run()
    {
        $this->artisan('debug')->assertExitCode(0);
    }

    public function test_it_prints_debug_information()
    {
        $this->artisan('debug')
            ->expectsOutput('HydePHP Debug Screen')
            ->expectsOutputToContain('Git Version:')
            ->expectsOutputToContain('Hyde Version:')
            ->expectsOutputToContain('Framework Version:')
            ->expectsOutputToContain('App Env:')
            ->expectsOutputToContain('Project directory:')
            ->expectsOutputToContain('Enabled features:')
            ->assertExitCode(0);
    }
}
