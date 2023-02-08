<?php

declare(strict_types=1);

namespace Hyde\Publications\Commands\Helpers;

use function array_shift;
use function explode;
use function fgets;
use Hyde\Hyde;
use function trim;

/**
 * Collects an array of lines from the standard input stream. Feed is terminated by a blank line.
 *
 * @internal
 *
 * @see \Hyde\Publications\Testing\Feature\InputStreamHandlerTest
 */
class InputStreamHandler
{
    public const TERMINATION_SEQUENCE = '<<<';

    private static ?array $mockedStreamBuffer = null;

    public static function call(): array
    {
        return (new self())->__invoke();
    }

    public function __invoke(): array
    {
        return $this->getLinesFromInputStream();
    }

    protected function getLinesFromInputStream(): array
    {
        $lines = [];
        do {
            $line = Hyde::stripNewlines($this->readInputStream());
            if ($this->shouldTerminate($line)) {
                break;
            }
            $lines[] = trim($line);
        } while (true);

        return $lines;
    }

    protected function shouldTerminate(string $line): bool
    {
        return $line === self::TERMINATION_SEQUENCE;
    }

    /** @codeCoverageIgnore Allows for mocking of the standard input stream */
    protected function readInputStream(): string
    {
        if (self::$mockedStreamBuffer !== null) {
            return array_shift(self::$mockedStreamBuffer) ?? '';
        }

        return fgets(STDIN);
    }

    /** @internal Allows for mocking of the standard input stream */
    public static function mockInput(string $input): void
    {
        self::$mockedStreamBuffer = explode("\n", $input);
    }
}
