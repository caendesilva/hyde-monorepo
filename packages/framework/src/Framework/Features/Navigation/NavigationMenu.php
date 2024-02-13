<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Navigation;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Arrayable;

class NavigationMenu
{
    /** @var \Illuminate\Support\Collection<string, \Hyde\Framework\Features\Navigation\NavItem> */
    protected Collection $items;

    public function __construct(Arrayable|array $items = [])
    {
        $this->items = new Collection($items);
    }
}
