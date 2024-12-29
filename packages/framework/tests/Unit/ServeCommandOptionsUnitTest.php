<?php

declare(strict_types=1);

namespace Hyde\Framework\Testing\Unit;

use Mockery;
use Hyde\Testing\UnitTestCase;
use Hyde\Foundation\HydeKernel;
use Illuminate\Process\Factory;
use Illuminate\Console\OutputStyle;
use Hyde\Console\Commands\ServeCommand;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

/**
 * @covers \Hyde\Console\Commands\ServeCommand
 *
 * @see \Hyde\Framework\Testing\Feature\Commands\ServeCommandTest
 */
class ServeCommandOptionsUnitTest extends UnitTestCase
{
    protected function setUp(): void
    {
        self::mockConfig([
            'hyde.server.host' => 'localhost',
            'hyde.server.port' => 8080,
        ]);

        Process::swap(new Factory());
        Process::preventStrayProcesses();
    }

    protected function tearDown(): void
    {
        $this->verifyMockeryExpectations();
    }

    public function testGetHostSelection()
    {
        $this->assertSame('localhost', $this->getMock()->getHostSelection());
    }

    public function testGetHostSelectionWithHostOption()
    {
        $this->assertSame('foo', $this->getMock(['host' => 'foo'])->getHostSelection());
    }

    public function testGetHostSelectionWithConfigOption()
    {
        self::mockConfig(['hyde.server.host' => 'foo']);

        $this->assertSame('foo', $this->getMock()->getHostSelection());
    }

    public function testGetHostSelectionWithHostOptionAndConfigOption()
    {
        self::mockConfig(['hyde.server.host' => 'foo']);

        $this->assertSame('bar', $this->getMock(['host' => 'bar'])->getHostSelection());
    }

    public function testGetPortSelection()
    {
        $this->assertSame(8080, $this->getMock()->getPortSelection());
    }

    public function testGetPortSelectionWithPortOption()
    {
        $this->assertSame(8081, $this->getMock(['port' => 8081])->getPortSelection());
    }

    public function testGetPortSelectionWithConfigOption()
    {
        self::mockConfig(['hyde.server.port' => 8082]);

        $this->assertSame(8082, $this->getMock()->getPortSelection());
    }

    public function testGetPortSelectionWithPortOptionAndConfigOption()
    {
        self::mockConfig(['hyde.server.port' => 8082]);

        $this->assertSame(8081, $this->getMock(['port' => 8081])->getPortSelection());
    }

    public function testGetEnvironmentVariables()
    {
        $this->assertSame([
            'HYDE_SERVER_REQUEST_OUTPUT' => true,
        ], $this->getMock()->getEnvironmentVariables());
    }

    public function testGetEnvironmentVariablesWithNoAnsiOption()
    {
        $this->assertSame([
            'HYDE_SERVER_REQUEST_OUTPUT' => false,
        ], $this->getMock(['no-ansi' => true])->getEnvironmentVariables());
    }

    public function testSavePreviewOptionPropagatesToEnvironmentVariables()
    {
        $command = $this->getMock(['save-preview' => 'false']);
        $this->assertSame('disabled', $command->getEnvironmentVariables()['HYDE_SERVER_SAVE_PREVIEW']);

        $command = $this->getMock(['save-preview' => 'true']);
        $this->assertSame('enabled', $command->getEnvironmentVariables()['HYDE_SERVER_SAVE_PREVIEW']);

        $command = $this->getMock(['save-preview' => '']);
        $this->assertSame('enabled', $command->getEnvironmentVariables()['HYDE_SERVER_SAVE_PREVIEW']);

        $command = $this->getMock(['save-preview' => null]);
        $this->assertFalse(isset($command->getEnvironmentVariables()['HYDE_SERVER_SAVE_PREVIEW']));

        $command = $this->getMock();
        $this->assertFalse(isset($command->getEnvironmentVariables()['HYDE_SERVER_SAVE_PREVIEW']));
    }

    public function testDashboardOptionPropagatesToEnvironmentVariables()
    {
        $command = $this->getMock(['dashboard' => 'false']);
        $this->assertSame('disabled', $command->getEnvironmentVariables()['HYDE_SERVER_DASHBOARD']);

        $command = $this->getMock(['dashboard' => 'true']);
        $this->assertSame('enabled', $command->getEnvironmentVariables()['HYDE_SERVER_DASHBOARD']);

        $command = $this->getMock(['dashboard' => '']);
        $this->assertSame('enabled', $command->getEnvironmentVariables()['HYDE_SERVER_DASHBOARD']);

        $command = $this->getMock(['dashboard' => null]);
        $this->assertFalse(isset($command->getEnvironmentVariables()['HYDE_SERVER_DASHBOARD']));

        $command = $this->getMock();
        $this->assertFalse(isset($command->getEnvironmentVariables()['HYDE_SERVER_DASHBOARD']));
    }

    public function testPrettyUrlsOptionPropagatesToEnvironmentVariables()
    {
        $command = $this->getMock(['pretty-urls' => 'false']);
        $this->assertSame('disabled', $command->getEnvironmentVariables()['HYDE_PRETTY_URLS']);

        $command = $this->getMock(['pretty-urls' => 'true']);
        $this->assertSame('enabled', $command->getEnvironmentVariables()['HYDE_PRETTY_URLS']);

        $command = $this->getMock(['pretty-urls' => '']);
        $this->assertSame('enabled', $command->getEnvironmentVariables()['HYDE_PRETTY_URLS']);

        $command = $this->getMock(['pretty-urls' => null]);
        $this->assertFalse(isset($command->getEnvironmentVariables()['HYDE_PRETTY_URLS']));

        $command = $this->getMock();
        $this->assertFalse(isset($command->getEnvironmentVariables()['HYDE_PRETTY_URLS']));
    }

    public function testPlayCdnOptionPropagatesToEnvironmentVariables()
    {
        $command = $this->getMock(['play-cdn' => 'false']);
        $this->assertSame('disabled', $command->getEnvironmentVariables()['HYDE_PLAY_CDN']);

        $command = $this->getMock(['play-cdn' => 'true']);
        $this->assertSame('enabled', $command->getEnvironmentVariables()['HYDE_PLAY_CDN']);

        $command = $this->getMock(['play-cdn' => '']);
        $this->assertSame('enabled', $command->getEnvironmentVariables()['HYDE_PLAY_CDN']);

        $command = $this->getMock(['play-cdn' => null]);
        $this->assertFalse(isset($command->getEnvironmentVariables()['HYDE_PLAY_CDN']));

        $command = $this->getMock();
        $this->assertFalse(isset($command->getEnvironmentVariables()['HYDE_PLAY_CDN']));
    }

    public function testParseEnvironmentOption()
    {
        $command = $this->getMock(['foo' => 'true']);
        $this->assertSame('enabled', $command->parseEnvironmentOption('foo'));

        $command = $this->getMock(['foo' => 'false']);
        $this->assertSame('disabled', $command->parseEnvironmentOption('foo'));
    }

    public function testParseEnvironmentOptionWithEmptyString()
    {
        $command = $this->getMock(['foo' => '']);
        $this->assertSame('enabled', $command->parseEnvironmentOption('foo'));
    }

    public function testParseEnvironmentOptionWithNull()
    {
        $command = $this->getMock(['foo' => null]);
        $this->assertNull($command->parseEnvironmentOption('foo'));
    }

    public function testParseEnvironmentOptionWithInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid boolean value for --foo option.');

        $command = $this->getMock(['foo' => 'bar']);
        $command->parseEnvironmentOption('foo');
    }

    public function testCheckArgvForOption()
    {
        $serverBackup = $_SERVER;

        $_SERVER['argv'] = ['--pretty-urls'];

        $command = $this->getMock();

        $this->assertSame('true', $command->checkArgvForOption('pretty-urls'));
        $this->assertNull($command->checkArgvForOption('dashboard'));

        $_SERVER = $serverBackup;
    }

    public function testWithOpenArgument()
    {
        HydeKernel::setInstance(new HydeKernel());

        $command = $this->getOpenServeCommandMock(['open' => true]);

        $command->safeHandle();

        $this->assertTrue($command->openInBrowserCalled);
    }

    public function testWithOpenArgumentWhenString()
    {
        HydeKernel::setInstance(new HydeKernel());

        $command = $this->getOpenServeCommandMock(['open' => '']);

        $command->safeHandle();

        $this->assertTrue($command->openInBrowserCalled);
    }

    public function testWithOpenArgumentWhenPath()
    {
        HydeKernel::setInstance(new HydeKernel());

        $command = $this->getOpenServeCommandMock(['open' => 'dashboard']);

        $command->safeHandle();

        $this->assertSame('dashboard', $command->openInBrowserPath);
    }

    public function testOpenInBrowser()
    {
        $output = $this->createMock(OutputStyle::class);
        $output->expects($this->never())->method('writeln');

        $command = $this->getMock(['--open' => true]);
        $command->setOutput($output);

        $binary = $this->getTestRunnerBinary();

        Process::shouldReceive('command')->once()->with("$binary http://localhost:8080")->andReturnSelf();
        Process::shouldReceive('run')->once()->andReturnSelf();
        Process::shouldReceive('failed')->once()->andReturn(false);

        $command->openInBrowser();
    }

    public function testOpenInBrowserWithPath()
    {
        Process::shouldReceive('command')->once()->with("{$this->getTestRunnerBinary()} http://localhost:8080/dashboard")->andReturnSelf();
        Process::shouldReceive('run')->once()->andReturnSelf();
        Process::shouldReceive('failed')->once()->andReturn(false);

        $this->getMock()->openInBrowser('dashboard');
    }

    public function testOpenInBrowserWithPathNormalizesPaths()
    {
        Process::shouldReceive('run')->andReturnSelf();
        Process::shouldReceive('failed')->andReturn(false);

        Process::shouldReceive('command')->times(3)->with("{$this->getTestRunnerBinary()} http://localhost:8080")->andReturnSelf();
        Process::shouldReceive('command')->once()->with("{$this->getTestRunnerBinary()} http://localhost:8080/dashboard")->andReturnSelf();
        Process::shouldReceive('command')->once()->with("{$this->getTestRunnerBinary()} http://localhost:8080/foo/bar")->andReturnSelf();

        $this->getMock()->openInBrowser('');
        $this->getMock()->openInBrowser('/');
        $this->getMock()->openInBrowser('//');
        $this->getMock()->openInBrowser('dashboard/');
        $this->getMock()->openInBrowser('foo/bar/');
    }

    public function testOpenInBrowserThatFails()
    {
        $output = Mockery::mock(OutputStyle::class);
        $output->shouldReceive('getFormatter')->andReturn($this->createMock(OutputFormatterInterface::class));

        $warning = '<warning>Unable to open the site preview in the browser on your system:</warning>';
        $context = '  Missing suitable \'open\' binary.';

        $output->shouldReceive('writeln')->once()->with($warning, OutputInterface::VERBOSITY_NORMAL);
        $output->shouldReceive('writeln')->once()->with($context, OutputInterface::VERBOSITY_NORMAL);
        $output->shouldReceive('newLine')->once();

        $command = $this->getMock(['--open' => true]);
        $command->setOutput($output);

        $binary = $this->getTestRunnerBinary();

        Process::shouldReceive('command')->once()->with("$binary http://localhost:8080")->andReturnSelf();
        Process::shouldReceive('run')->once()->andReturnSelf();
        Process::shouldReceive('failed')->once()->andReturn(true);
        Process::shouldReceive('errorOutput')->once()->andReturn("Missing suitable 'open' binary.");

        $command->openInBrowser();
    }

    public function testGetOpenCommandForWindows()
    {
        $this->assertSame('start', $this->getMock()->getOpenCommand('Windows'));
    }

    public function testGetOpenCommandForDarwin()
    {
        $this->assertSame('open', $this->getMock()->getOpenCommand('Darwin'));
    }

    public function testGetOpenCommandForLinux()
    {
        $this->assertSame('xdg-open', $this->getMock()->getOpenCommand('Linux'));
    }

    public function testGetOpenCommandForUnknownOS()
    {
        $this->assertNull($this->getMock()->getOpenCommand('UnknownOS'));
    }

    public function testWithViteArgument()
    {
        HydeKernel::setInstance(new HydeKernel());

        $command = $this->getViteServeCommandMock(['vite' => true]);

        $command->safeHandle();

        $this->assertTrue($command->viteProcessStarted);
    }

    protected function getTestRunnerBinary(): string
    {
        return match (PHP_OS_FAMILY) {
            'Darwin' => 'open',
            'Windows' => 'start',
            default => 'xdg-open',
        };
    }

    protected function getMock(array $options = []): ServeCommandMock
    {
        return new ServeCommandMock($options);
    }

    protected function getOpenServeCommandMock(array $arguments): ServeCommandMock
    {
        return new class($arguments) extends ServeCommandMock
        {
            public bool $openInBrowserCalled = false;
            public string $openInBrowserPath = '';

            // Void unrelated methods
            protected function configureOutput(): void
            {
            }

            protected function printStartMessage(): void
            {
            }

            protected function runServerProcess(string $command): void
            {
                $this->server = Mockery::mock(\Illuminate\Contracts\Process\InvokedProcess::class);
                $this->server->shouldReceive('running')->once()->andReturn(false);
            }

            protected function openInBrowser(string $path = '/'): void
            {
                $this->openInBrowserCalled = true;
                $this->openInBrowserPath = $path;
            }
        };
    }

    protected function getViteServeCommandMock(array $arguments): ServeCommandMock
    {
        return new class($arguments) extends ServeCommandMock
        {
            public bool $viteProcessStarted = false;

            // Void unrelated methods
            protected function configureOutput(): void
            {
            }

            protected function printStartMessage(): void
            {
            }

            protected function runServerProcess(string $command): void
            {
                $this->server = Mockery::mock(\Illuminate\Contracts\Process\InvokedProcess::class);
                $this->server->shouldReceive('running')->once()->andReturn(false);
            }

            protected function runViteProcess(): void
            {
                $this->viteProcessStarted = true;
            }

            protected function openInBrowser(string $path = '/'): void
            {
            }
        };
    }
}

/**
 * @method getHostSelection
 * @method getPortSelection
 * @method getEnvironmentVariables
 * @method parseEnvironmentOption(string $name)
 * @method checkArgvForOption(string $name)
 * @method openInBrowser(string $path = '/')
 */
class ServeCommandMock extends ServeCommand
{
    public function __construct(array $options = [])
    {
        parent::__construct();

        $this->input = new InputMock($options);
    }

    public function __call($method, $parameters)
    {
        return call_user_func_array([$this, $method], $parameters);
    }

    public function option($key = null)
    {
        return $this->input->getOption($key);
    }

    public function getOpenCommand(string $osFamily): ?string
    {
        return parent::getOpenCommand($osFamily);
    }
}

class InputMock
{
    protected array $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function getOption(string $key)
    {
        return $this->options[$key] ?? null;
    }
}
