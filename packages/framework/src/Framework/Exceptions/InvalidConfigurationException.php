<?php

declare(strict_types=1);

namespace Hyde\Framework\Exceptions;

use Throwable;
use Hyde\Facades\Filesystem;
use InvalidArgumentException;

use function assert;
use function explode;
use function realpath;

class InvalidConfigurationException extends InvalidArgumentException
{
    public function __construct(string $message = 'Invalid configuration detected.', ?string $namespace = null, ?string $key = null, ?Throwable $previous = null)
    {
        if ($namespace && $key) {
            [$this->file, $this->line] = $this->findConfigLine($namespace, $key);
        }

        parent::__construct($message, previous: $previous);
    }

    /**
     * @experimental Please report any issues with this method to the authors at https://github.com/hydephp/develop/issues
     *
     * @return array{string, int}
     */
    protected function findConfigLine(string $namespace, string $key): array
    {
        $file = realpath("config/$namespace.php");
        $contents = Filesystem::getContents($file);
        $lines = explode("\n", $contents);

        foreach ($lines as $line => $content) {
            if (str_contains($content, "'$key' =>")) {
                break;
            }
        }

        assert($file !== false);
        assert(isset($line));

        return [$file, $line + 1];
    }

    /**
     * @internal
     *
     * @experimental
     */
    public static function try(callable $callback, ?string $message = null): mixed
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            throw new static($message ?? $exception->getMessage(), previous: $exception);
        }
    }
}
