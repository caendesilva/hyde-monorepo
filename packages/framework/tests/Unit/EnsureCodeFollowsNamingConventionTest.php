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

        // Commands must not start with "Hyde" and must end with "Command"
        foreach ($files as $filepath) {
            $filename = basename($filepath, '.php');
            $this->assertStringStartsNotWith('Hyde', $filename);
            $this->assertStringEndsWith('Command', $filename);
        }
    }

    public function testActionEntryPointsFollowNamingConventions()
    {
        $files = glob('vendor/hyde/framework/src/Framework/Actions/*.php');

        $this->assertNotEmpty($files, 'No action classes found.');

        // Actions must have either a public static handle() method or a public non-static execute() method
        foreach ($files as $filepath) {
            $class = 'Hyde\\Framework\\Actions\\' . basename($filepath, '.php');
            $reflection = new \ReflectionClass($class);

            $hasHandleMethod = $reflection->hasMethod('handle') && $reflection->getMethod('handle')->isPublic() && $reflection->getMethod('handle')->isStatic();
            $hasExecuteMethod = $reflection->hasMethod('execute') && $reflection->getMethod('execute')->isPublic() && ! $reflection->getMethod('execute')->isStatic();

            $this->assertTrue($hasHandleMethod || $hasExecuteMethod,
                "Action class $class does not have a public static handle() method or a public non-static execute() method.\n ".  realpath($filepath)
            );
        }
    }
}
