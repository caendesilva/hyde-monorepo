<?php

declare(strict_types=1);

namespace Hyde\Foundation\Concerns;

use Hyde\Support\Models\Route;
use Hyde\Support\Filesystem\MediaFile;

/**
 * @internal Single-use trait for the HydeKernel class.
 *
 * @see \Hyde\Foundation\HydeKernel
 */
trait ForwardsHyperlinks
{
    public function formatLink(string $destination): string
    {
        return $this->hyperlinks->formatLink($destination);
    }

    public function relativeLink(string $destination): string
    {
        return $this->hyperlinks->relativeLink($destination);
    }

    /** @throws \Hyde\Framework\Exceptions\FileNotFoundException If the file does not exist in the `_media` source directory. */
    public function asset(string $name): MediaFile
    {
        return $this->hyperlinks->asset($name);
    }

    public function url(string $path = ''): string
    {
        return $this->hyperlinks->url($path);
    }

    public function route(string $key): ?Route
    {
        return $this->hyperlinks->route($key);
    }

    public function hasSiteUrl(): bool
    {
        return $this->hyperlinks->hasSiteUrl();
    }
}
