<?php

declare(strict_types=1);

namespace Hyde\Foundation\Concerns;

use Hyde\Foundation\Kernel\Filesystem;
use Illuminate\Support\Collection;

/**
 * @internal Single-use trait for the HydeKernel class.
 *
 * @see \Hyde\Foundation\HydeKernel
 */
trait ForwardsFilesystem
{
    public function filesystem(): Filesystem
    {
        return $this->filesystem;
    }

    public function path(string $path = ''): string
    {
        return $this->filesystem->path($path);
    }

    public function vendorPath(string $path = '', string $package = 'framework'): string
    {
        return $this->filesystem->vendorPath($path, $package);
    }

    public function sitePath(string $path = ''): string
    {
        return $this->filesystem->sitePath($path);
    }

    public function pathToAbsolute(string|array $path): string|array
    {
        return $this->filesystem->pathToAbsolute($path);
    }

    public function pathToRelative(string $path): string
    {
        return $this->filesystem->pathToRelative($path);
    }

    /** @return \Illuminate\Support\Collection<string, \Hyde\Support\Filesystem\MediaFile> */
    public function assets(): Collection
    {
        return $this->filesystem->assets();
    }
}
