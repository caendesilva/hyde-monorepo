<?php

declare(strict_types=1);

namespace Hyde\RealtimeCompiler;

use Closure;
use Hyde\Facades\Vite;
use Hyde\Hyde;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyOutput;

use function Termwind\render;

class ConsoleOutput
{
    protected bool $verbose;
    protected SymfonyOutput $output;

    public function __construct(bool $verbose = false, ?SymfonyOutput $output = null)
    {
        $this->verbose = $verbose;
        $this->output = $output ?? new SymfonyOutput();
    }

    public function printStartMessage(string $host, int $port, array $environment = [], ?bool $willUseVite = false): void
    {
        $url = sprintf('%s://%s:%d', $port === 443 ? 'https' : 'http', $host, $port);

        $lines = Arr::whereNotNull([
            '',
            sprintf('<span class="text-blue-500">%s</span> <span class="text-gray">%s</span>', 'HydePHP Realtime Compiler', 'v'.Hyde::getInstance()->version()),
            '',
            sprintf('<span class="text-white">Listening on:</span> <a href="%s" class="text-yellow-500">%s</a>', $url, $url),
            (config('hyde.server.dashboard.enabled') || Arr::has($environment, 'HYDE_SERVER_DASHBOARD')) && Arr::get($environment, 'HYDE_SERVER_DASHBOARD') === 'enabled' ?
                sprintf('<span class="text-white">Live dashboard:</span> <a href="%s/dashboard" class="text-yellow-500">%s/dashboard</a>', $url, $url) : null,
            (Vite::running() || $willUseVite) ?
                sprintf('<span class="text-white">Vite HMR server:</span> <a href="http://%s:5173" class="text-yellow-500">http://%s:5173</a>', $host, $host) : null,
            '',
        ]);

        $lineLength = max(array_map('strlen', array_map('strip_tags', $lines)));

        $lines = array_map(function (string $line) use ($lineLength): string {
            return sprintf('&nbsp;│&nbsp;<span class="text-white">%s</span>%s│',
                $line, str_repeat('&nbsp;', ($lineLength - strlen(strip_tags($line))) + 1)
            );
        }, $lines);

        $topLine = sprintf('&nbsp;╭%s╮', str_repeat('─', $lineLength + 2));
        $bottomLine = sprintf('&nbsp;╰%s╯', str_repeat('─', $lineLength + 2));

        $body = implode('<br>', array_merge([''], [$topLine], $lines, [$bottomLine], ['']));

        render("<div class=\"text-green-500\">$body</div>");
    }

    public function getFormatter(): Closure
    {
        return function (string $type, string $line): void {
            $this->handleOutput($line);
        };
    }

    /** @experimental */
    public function printMessage(string $message, string $context): void
    {
        $this->output->writeln(sprintf('%s ::context=[%s]', $message, $context));
    }

    protected function handleOutput(string $buffer): void
    {
        str($buffer)->trim()->explode("\n")->each(function (string $line): void {
            $this->renderLine($this->formatLineForOutput($line));
        });
    }

    protected function renderLine(?string $line): void
    {
        if ($line !== null) {
            render($line);
        }
    }

    protected function formatLineForOutput(string $line): ?string
    {
        if (str_contains($line, 'Development Server (http:')) {
            return $this->formatServerStartedLine($line);
        }
        if (str_contains($line, ']: ')) {
            return $this->formatRequestLine($line);
        }
        if (str_ends_with(trim($line), 'Accepted') || str_ends_with(trim($line), 'Closing')) {
            return $this->verbose ? $this->formatRequestStatusLine($line) : null;
        }
        if (str_contains($line, '::context=')) {
            return $this->formatContextLine($line);
        }

        return $this->formatLine($line, Carbon::now());
    }

    protected function formatServerStartedLine(string $line): string
    {
        return $this->formatLine(sprintf('PHP %s Development Server started. <span class="text-yellow-500">Use Ctrl+C to stop.</span>', PHP_VERSION), $this->parseDate($line), 'green-500');
    }

    protected function formatRequestLine(string $line): string
    {
        $dateString = Str::betweenFirst($line, '[', ']');
        $message = substr($line, strlen($dateString) + 3);

        $statusCode = Str::between($message, ' [', ']:');
        if ($statusCode >= 400) {
            $message = Str::replaceLast($statusCode, sprintf('<span class="text-red-500">%s</span>', $statusCode), $message);
            $iconColor = 'yellow-500';
        }

        return $this->formatLine($message, $this->parseDate($line), $iconColor ?? 'blue-500');
    }

    protected function formatRequestStatusLine(string $line): string
    {
        $address = trim(Str::between($line, ']', ' '));
        $status = str_contains($line, 'Accepted') ? 'Accepted' : 'Closing';

        return $this->formatLine(sprintf('%s %s', $address, $status), $this->parseDate($line));
    }

    protected function formatContextLine(string $line): string
    {
        $message = trim(Str::before($line, '::context='), '[]');
        $context = Str::between($line, '::context=[', ']');
        $success = str_contains($message, 'Created') || str_contains($message, 'Updated');

        return $this->formatLine($message, Carbon::now(), $success ? 'green-500' : 'blue-500', $context);
    }

    protected function formatLine(string $message, Carbon $date, string $iconColor = 'blue-500', string $context = ''): string
    {
        if ($context) {
            $context = "$context ";
        }

        return sprintf(<<<'HTML'
            <div class="flex w-full justify-between">
                <span>
                    <span class="text-%s">i</span>
                    %s
                </span>
                <span class="text-gray">%s%s</span>
            </div>
            HTML,
            $iconColor, $message, $context, $date->format('Y-m-d H:i:s')
        );
    }

    protected function parseDate(string $line): Carbon
    {
        return Carbon::parse(Str::betweenFirst($line, '[', ']'));
    }
}
