<?php

namespace Hyde\Framework\Modules\Router\Concerns;

interface Routable
{
    public static function getRouteSourcePath(): string;
    public static function getRouteOutputPath(): string;
}
