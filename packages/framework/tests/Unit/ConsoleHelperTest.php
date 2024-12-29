<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Hyde\Console\Helpers\ConsoleHelper;
use Hyde\Testing\UnitTestCase;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @covers \Hyde\Console\Helpers\ConsoleHelper
 */
class ConsoleHelperTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        ConsoleHelper::clearMocks();
    }

    public function testCanMockWindowsOs()
    {
        $input = $this->createMock(InputInterface::class);
        $input->method('isInteractive')->willReturn(true);

        ConsoleHelper::mockWindowsOs(false);

        $this->assertTrue(ConsoleHelper::canUseLaravelPrompts($input));

        ConsoleHelper::mockWindowsOs(true);

        $this->assertFalse(ConsoleHelper::canUseLaravelPrompts($input));
    }
}
