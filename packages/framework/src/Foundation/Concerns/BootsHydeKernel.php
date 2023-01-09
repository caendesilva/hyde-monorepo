<?php

declare(strict_types=1);

namespace Hyde\Foundation\Concerns;

use Hyde\Foundation\FileCollection;
use Hyde\Foundation\PageCollection;
use Hyde\Foundation\RouteCollection;

/**
 * @internal Single-use trait for the HydeKernel class.
 *
 * @see \Hyde\Foundation\HydeKernel
 */
trait BootsHydeKernel
{
    private bool $readyToBoot = false;
    private bool $booting = false;

    public function boot(): void
    {
        if ($this->booting) {
            return;
        }

        $this->booting = true;

        $this->files = FileCollection::boot($this);
        $this->pages = PageCollection::boot($this);
        $this->routes = RouteCollection::boot($this);

        $this->booting = false;
        $this->booted = true;
    }

    /** @internal */
    public function readyToBoot(): void
    {
        $this->readyToBoot = true;
    }
}
