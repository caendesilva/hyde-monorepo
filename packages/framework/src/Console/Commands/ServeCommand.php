<?php

declare(strict_types=1);

namespace Hyde\Console\Commands;

use Closure;
use Hyde\Hyde;
use Hyde\Facades\Config;
use Illuminate\Support\Arr;
use Hyde\RealtimeCompiler\ConsoleOutput;
use Illuminate\Support\Facades\Process;
use LaravelZero\Framework\Commands\Command;
use Hyde\Publications\Commands\ValidatingCommand;

use function sprintf;
use function class_exists;

/**
 * Start the realtime compiler server.
 *
 * @see https://github.com/hydephp/realtime-compiler
 */
class ServeCommand extends ValidatingCommand
{
    /** @var string */
    protected $signature = 'serve 
        {--host= : <comment>[default: "localhost"]</comment>}}
        {--port= : <comment>[default: 8080]</comment>}
        {--dashboard= : Enable the realtime compiler dashboard. (Overrides config setting)}
        {--pretty-urls= : Enable pretty URLs. (Overrides config setting)}
    ';

    /** @var string */
    protected $description = 'Start the realtime compiler server.';

    protected ConsoleOutput $console;

    public function safeHandle(): int
    {
        $this->configureOutput();
        $this->printStartMessage();

        $this->runServerProcess(sprintf('php -S %s:%d %s',
            $this->getHostSelection(),
            $this->getPortSelection(),
            $this->getExecutablePath()
        ));

        return Command::SUCCESS;
    }

    protected function getHostSelection(): string
    {
        return (string) $this->option('host') ?: Config::getString('hyde.server.host', 'localhost');
    }

    protected function getPortSelection(): int
    {
        return (int) ($this->option('port') ?: Config::getInt('hyde.server.port', 8080));
    }

    protected function getExecutablePath(): string
    {
        return Hyde::path('vendor/hyde/realtime-compiler/bin/server.php');
    }

    protected function runServerProcess(string $command): void
    {
        Process::forever()->env($this->getEnvironmentVariables())->run($command, $this->getOutputHandler());
    }

    protected function getEnvironmentVariables(): array
    {
        $vars = [
            'HYDE_RC_REQUEST_OUTPUT' => ! $this->option('no-ansi'),
        ];

        $vars['HYDE_RC_SERVER_DASHBOARD'] = $this->parseEnvironmentOption('dashboard');
        $vars['HYDE_PRETTY_URLS'] = $this->parseEnvironmentOption('pretty-urls');

        return Arr::whereNotNull($vars);
    }

    protected function configureOutput(): void
    {
        if (! $this->useBasicOutput()) {
            $this->console = new ConsoleOutput($this->output->isVerbose());
        }
    }

    protected function printStartMessage(): void
    {
        $this->useBasicOutput()
            ? $this->output->writeln('<info>Starting the HydeRC server...</info> Press Ctrl+C to stop')
            : $this->console->printStartMessage($this->getHostSelection(), $this->getPortSelection());
    }

    protected function getOutputHandler(): Closure
    {
        return $this->useBasicOutput() ? function (string $type, string $line): void {
            $this->output->write($line);
        } : $this->console->getFormatter();
    }

    protected function useBasicOutput(): bool
    {
        return $this->option('no-ansi') || ! class_exists(ConsoleOutput::class);
    }

    protected function parseEnvironmentOption(string $name): ?string
    {
        if ($this->option($name) !== null) {
            return match ($this->option($name)) {
                'true', '' => 'enabled',
                'false' => 'disabled',
                default => null
            };
        }

        return null;
    }
}
