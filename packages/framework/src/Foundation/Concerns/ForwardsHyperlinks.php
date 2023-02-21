<?php

declare(strict_types=1);

namespace Hyde\Foundation\Concerns;

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

    public function mediaLink(string $destination, bool $validate = true): string
    {
        return $this->hyperlinks->mediaLink($destination, $validate);
    }

    public function image(string $name, bool $preferQualifiedUrl = false): string
    {
        return $this->hyperlinks->image($name, $preferQualifiedUrl);
    }

    public function hasSiteUrl(): bool
    {
        return $this->hyperlinks->hasSiteUrl();
    }

    public function url(string $path = ''): string
    {
        return $this->hyperlinks->url($path);
    }
}
