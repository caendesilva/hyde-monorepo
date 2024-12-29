<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\BuildTasks;

use Throwable;
use Hyde\Hyde;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Command\Command;
use Hyde\Framework\Concerns\TracksExecutionTime;
use Illuminate\Console\Concerns\InteractsWithIO;

use function str_replace;
use function sprintf;

abstract class BuildTask
{
    use InteractsWithIO;
    use TracksExecutionTime;

    /** @var string The message that will be displayed when the task is run. */
    protected static string $message = 'Running build task';

    protected int $exitCode = Command::SUCCESS;

    /** @var \Illuminate\Console\OutputStyle|null */
    protected $output;

    abstract public function handle(): void;

    /**
     * This method is called by the BuildTaskService. It will run the task using the handle method,
     * as well as write output to the console, and handle any exceptions that may occur.
     *
     * @return int The exit code of the task. This can be used when calling a task directly from a command.
     */
    public function run(?OutputStyle $output = null): int
    {
        $this->startClock();

        if ($output && ! $this->output) {
            $this->setOutput($output);
        }

        $this->printStartMessage();

        try {
            $this->handle();
            $this->printFinishMessage();
        } catch (Throwable $exception) {
            if ($exception instanceof BuildTaskSkippedException) {
                $this->write("<bg=yellow>Skipped</>\n");
                $this->write("<fg=gray> > {$exception->getMessage()}</>");
            } else {
                $this->write("<error>Failed</error>\n");
                $this->write("<error>{$exception->getMessage()}</error>");
            }

            $this->exitCode = $exception->getCode();
        }

        $this->write("\n");

        return $this->exitCode;
    }

    public function printStartMessage(): void
    {
        $this->write("<comment>{$this->getMessage()}...</comment> ");
    }

    public function printFinishMessage(): void
    {
        $this->writeln('<fg=gray>Done in '.$this->getExecutionTimeString().'</>');
    }

    public function getMessage(): string
    {
        return static::$message;
    }

    public function write(string $message): void
    {
        $this->output?->write($message);
    }

    public function writeln(string $message): void
    {
        $this->output?->writeln($message);
    }

    /**
     * Write a fluent message to the output that the task is skipping and halt the execution.
     *
     * @throws \Hyde\Framework\Features\BuildTasks\BuildTaskSkippedException
     */
    public function skip(string $reason = 'Task was skipped'): void
    {
        throw new BuildTaskSkippedException($reason);
    }

    /** Write a fluent message to the output that the task created the specified file. */
    public function createdSiteFile(string $path): static
    {
        $this->write(sprintf(
            "\n > Created <info>%s</info>",
            str_replace('\\', '/', Hyde::pathToRelative($path))
        ));

        return $this;
    }

    /** Write a fluent message to the output with the execution time of the task. */
    public function withExecutionTime(): static
    {
        $this->write(" in {$this->getExecutionTimeString()}");

        return $this;
    }
}
