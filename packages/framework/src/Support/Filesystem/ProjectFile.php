<?php

declare(strict_types=1);

namespace Hyde\Support\Filesystem;

use function basename;
use Hyde\Facades\Filesystem;
use Hyde\Hyde;
use Hyde\Support\Concerns\Serializable;
use Hyde\Support\Contracts\SerializableContract;
use function pathinfo;
use Stringable;

/**
 * Filesystem abstraction for a file stored in the project.
 *
 * @see \Hyde\Framework\Testing\Feature\FileTest
 */
abstract class ProjectFile implements SerializableContract, Stringable
{
    use Serializable;

    /**
     * @var string The path relative to the project root.
     *
     * @example `_pages/index.blade.php`
     * @example `_media/logo.png`
     */
    public readonly string $path;

    public static function make(string $path, mixed ...$args): static
    {
        return new static($path, ...$args);
    }

    public function __construct(string $path)
    {
        $this->path = Hyde::pathToRelative($path);
    }

    public function __toString(): string
    {
        return $this->path;
    }

    /**
     * @return array{name: string, path: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'path' => $this->getPath(),
        ];
    }

    public function getName(): string
    {
        return basename($this->path);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getAbsolutePath(): string
    {
        return Hyde::path($this->path);
    }

    public function getContents(): string
    {
        return Filesystem::getContents($this->path);
    }

    public function getExtension(): string
    {
        return pathinfo($this->getAbsolutePath(), PATHINFO_EXTENSION);
    }
}
