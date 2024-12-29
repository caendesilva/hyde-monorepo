<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Mockery;
use Closure;
use Hyde\Hyde;
use Exception;
use Hyde\Testing\UnitTestCase;
use Illuminate\Console\OutputStyle;
use Hyde\Framework\Features\BuildTasks\BuildTask;

/**
 * @covers \Hyde\Framework\Features\BuildTasks\BuildTask
 */
class BuildTaskUnitTest extends UnitTestCase
{
    public function testCanCreateBuildTask()
    {
        $task = new EmptyTestBuildTask();

        $this->assertInstanceOf(BuildTask::class, $task);
    }

    public function testItTracksExecutionTime()
    {
        $task = new InspectableTestBuildTask();

        $task->run();

        $this->assertTrue($task->isset('timeStart'));
        $this->assertGreaterThan(0, $task->property('timeStart'));
    }

    public function testItCanRunWithoutOutput()
    {
        $task = new InspectableTestBuildTask();

        $task->run();

        $this->assertFalse($task->isset('output'));
    }

    public function testItCanRunWithOutput()
    {
        $task = new InspectableTestBuildTask();

        $output = Mockery::mock(OutputStyle::class, [
            'write' => null,
            'writeln' => null,
        ]);

        $task->run($output);
        $this->assertTrue($task->isset('output'));
    }

    public function testItPrintsStartMessage()
    {
        $task = tap(new BufferedTestBuildTask(), fn ($task) => $task->run());

        $this->assertStringContainsString('Running build task', $task->buffer[0]);
    }

    public function testItPrintsFinishMessage()
    {
        $task = tap(new BufferedTestBuildTask(), fn ($task) => $task->run());

        $this->assertStringContainsString('Done in', $task->buffer[1]);
        $this->assertStringContainsString('ms', $task->buffer[1]);
    }

    public function testRunMethodHandlesTask()
    {
        $task = new InspectableTestBuildTask();

        $this->assertFalse($task->property('wasHandled'));

        $task->run();

        $this->assertTrue($task->property('wasHandled'));
    }

    public function testRunMethodReturnsExitCode()
    {
        $task = new InspectableTestBuildTask();

        $this->assertSame(0, $task->run());

        $task->set('exitCode', 1);

        $this->assertSame(1, $task->run());
    }

    public function testCanGetMessage()
    {
        $this->assertSame('Running build task', (new BufferedTestBuildTask())->getMessage());
    }

    public function testCanGetCustomMessage()
    {
        $task = new BufferedTestBuildTaskWithCustomMessage();

        $this->assertSame('Custom message', $task->getMessage());
    }

    public function testCanPrintStartMessage()
    {
        $task = tap(new BufferedTestBuildTask(), fn ($task) => $task->printStartMessage());

        $this->assertSame('<comment>Running build task...</comment>', trim($task->buffer[0]));
    }

    public function testCanPrintCustomStartMessage()
    {
        $task = tap(new BufferedTestBuildTaskWithCustomMessage(), fn ($task) => $task->printStartMessage());

        $this->assertSame('<comment>Custom message...</comment>', trim($task->buffer[0]));
    }

    public function testCanPrintFinishMessage()
    {
        $task = tap(new BufferedTestBuildTask(), function ($task) {
            $task->set('timeStart', time());
            $task->printFinishMessage();
        });

        $this->assertStringContainsString('Done in', $task->buffer[0]);
        $this->assertStringContainsString('ms', $task->buffer[0]);
    }

    public function testFinishMessagePrintingFormatsExecutionTime()
    {
        $task = tap(new BufferedTestBuildTask(), function ($task) {
            $task->set('timeStart', 1000);
            $task->mockClock(1001.23456);
            $task->printFinishMessage();
        });

        $this->assertSame('<fg=gray>Done in 1,234.56ms</>', $task->buffer[0]);
    }

    public function testCanWriteToOutput()
    {
        $task = new BufferedTestBuildTask();

        $task->write('foo');
        $task->writeln('bar');

        $this->assertSame(['foo', 'bar'], $task->buffer);
    }

    public function testCreatedSiteFile()
    {
        self::resetKernel();

        $task = new BufferedTestBuildTask();

        $task->createdSiteFile('foo');

        $this->assertSame("\n > Created <info>foo</info>", $task->buffer[0]);
    }

    public function testCreatedSiteFileWithAbsolutePath()
    {
        self::resetKernel();

        $task = new BufferedTestBuildTask();

        $task->createdSiteFile(Hyde::path('foo'));

        $this->assertSame("\n > Created <info>foo</info>", $task->buffer[0]);
    }

    public function testWithExecutionTime()
    {
        $task = tap(new BufferedTestBuildTask(), function (BufferedTestBuildTask $task) {
            $task->set('timeStart', 1000);
            $task->mockClock(1001.23456);
        });

        $this->assertSame($task, $task->withExecutionTime());
        $this->assertSame(' in 1,234.56ms', $task->buffer[0]);
    }

    public function testTaskSkipping()
    {
        $task = tap(new BufferedTestBuildTask(), function (BufferedTestBuildTask $task) {
            $task->mockHandle(function (BufferedTestBuildTask $task) {
                $task->skip();
            })->run();
        });

        $this->assertSame(3, $task->property('exitCode'));
        $this->assertSame('<bg=yellow>Skipped</>', trim($task->buffer[1]));
        $this->assertSame('<fg=gray> > Task was skipped</>', $task->buffer[2]);
    }

    public function testTaskSkippingWithCustomMessage()
    {
        $task = tap(new BufferedTestBuildTask(), function (BufferedTestBuildTask $task) {
            $task->mockHandle(function (BufferedTestBuildTask $task) {
                $task->skip('Custom reason');
            })->run();
        });

        $this->assertSame(3, $task->property('exitCode'));
        $this->assertSame('<bg=yellow>Skipped</>', trim($task->buffer[1]));
        $this->assertSame('<fg=gray> > Custom reason</>', $task->buffer[2]);
    }

    public function testExceptionHandling()
    {
        $task = new BufferedTestBuildTask();

        $task->mockHandle(function () {
            throw new Exception('Test exception', 123);
        });

        $task->run();

        $this->assertSame(123, $task->property('exitCode'));
        $this->assertSame('<error>Failed</error>', trim($task->buffer[1]));
        $this->assertSame('<error>Test exception</error>', $task->buffer[2]);
    }
}

class EmptyTestBuildTask extends BuildTask
{
    public function handle(): void
    {
        // Do nothing
    }
}

class InspectableTestBuildTask extends BuildTask
{
    protected bool $wasHandled = false;
    protected float $mockedEndTime;
    protected Closure $mockHandle;

    public function handle(): void
    {
        if (isset($this->mockHandle)) {
            ($this->mockHandle)($this);
        } else {
            $this->wasHandled = true;
        }
    }

    public function isset(string $name): bool
    {
        return isset($this->{$name});
    }

    public function property(string $name): mixed
    {
        return $this->{$name};
    }

    public function call(string $name, mixed ...$args): mixed
    {
        return $this->{$name}(...$args);
    }

    public function set(string $name, mixed $value): void
    {
        $this->{$name} = $value;
    }

    public function mockClock(float $time): void
    {
        $this->mockedEndTime = $time;
    }

    public function mockHandle(Closure $handle): static
    {
        $this->mockHandle = $handle;

        return $this;
    }

    protected function stopClock(): float
    {
        if (isset($this->mockedEndTime)) {
            return $this->mockedEndTime - $this->timeStart;
        }

        return parent::stopClock();
    }
}

class BufferedTestBuildTask extends InspectableTestBuildTask
{
    public array $buffer = [];

    public function write(string $message): void
    {
        $this->buffer[] = $message;
    }

    public function writeln(string $message): void
    {
        $this->buffer[] = $message;
    }
}

class BufferedTestBuildTaskWithCustomMessage extends BufferedTestBuildTask
{
    protected static string $message = 'Custom message';
}
