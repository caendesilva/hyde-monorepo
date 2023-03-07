<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Testing\UnitTestCase;

class EnsureCodeFollowsNamingConventionTest extends UnitTestCase
{
    public function testCommandsClassesFollowNamingConventions()
    {
        $files = glob('vendor/hyde/framework/src/Console/Commands/*.php');

        $this->assertNotEmpty($files, 'No commands found.');

        foreach ($files as $filepath) {
            $filename = basename($filepath, '.php');
            $this->assertStringStartsNotWith('Hyde', $filename);
            $this->assertStringEndsWith('Command', $filename);
        }
    }
}
