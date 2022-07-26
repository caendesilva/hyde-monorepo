<?php

namespace Hyde\Framework\Contracts;

interface AssetServiceContract
{
    /**
     * The HydeFront version to load.
     */
    public function version(): string;

    /**
     * Return the main Hyde stylesheet location/path.
     */
    public function stylePath(): string;

    /**
     * Construct a URI path for the CDN using the static dist version.
     */
    public function constructCdnPath(string $file): string;
}
