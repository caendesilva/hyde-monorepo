<?php

declare(strict_types=1);

namespace Hyde\Console\Commands\Helpers;

use function array_shift;
use function explode;
use function fgets;
use Hyde\Framework\Concerns\InvokableAction;
use Illuminate\Support\Str;
use function trim;

/**
 * Collects an array of lines from the standard input stream. Feed is terminated by a blank line.
 *
 * @todo Add dynamic support for detecting and using comma separated values?
 */
class InputStreamHandler extends InvokableAction
{
    private static ?array $mockedStreamBuffer = null;

    public function __invoke(): array
    {
        return $this->getLinesFromInputStream();
    }

    protected function getLinesFromInputStream(): array
    {
        $lines = [];
        do {
            $line = Str::replace(["\n", "\r"], '', $this->readInputStream());
            if ($line === '') {
                break;
            }
            $lines[] = trim($line);
        } while (true);

        return $lines;
    }

    /** @codeCoverageIgnore Allows for mocking of the standard input stream */
    protected function readInputStream(): string
    {
        if (self::$mockedStreamBuffer) {
            return array_shift(self::$mockedStreamBuffer);
        }

        return fgets(STDIN);
    }

    /** @internal Allows for mocking of the standard input stream */
    public static function mockInput(string $input): void
    {
        self::$mockedStreamBuffer = explode("\n", $input);
    }
}
