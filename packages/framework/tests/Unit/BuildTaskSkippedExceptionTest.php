<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Framework\Features\BuildTasks\BuildTaskSkippedException;
use Hyde\Testing\UnitTestCase;

/**
 * @covers \Hyde\Framework\Features\BuildTasks\BuildTaskSkippedException
 */
class BuildTaskSkippedExceptionTest extends UnitTestCase
{
    public function testItCanBeInstantiated()
    {
        $exception = new BuildTaskSkippedException();

        $this->assertInstanceOf(BuildTaskSkippedException::class, $exception);
    }

    public function testItThrowsAnExceptionWithDefaultMessage()
    {
        $this->expectException(BuildTaskSkippedException::class);
        $this->expectExceptionMessage('Task was skipped');

        throw new BuildTaskSkippedException();
    }

    public function testItThrowsAnExceptionWithCustomMessage()
    {
        $this->expectException(BuildTaskSkippedException::class);
        $this->expectExceptionMessage('Custom message');

        throw new BuildTaskSkippedException('Custom message');
    }

    public function testDefaultExceptionCode()
    {
        $exception = new BuildTaskSkippedException();

        $this->assertSame(3, $exception->getCode());
    }

    public function testCustomExceptionCode()
    {
        $exception = new BuildTaskSkippedException('Custom message', 123);

        $this->assertSame(123, $exception->getCode());
    }
}
