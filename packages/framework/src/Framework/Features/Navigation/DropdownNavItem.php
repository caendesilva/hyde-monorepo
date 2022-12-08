<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Navigation;

class DropdownNavItem extends NavItem
{
    /** @var array<NavItem> */
    public array $items;
    public string $name;
    public string $href = '#';

    public function __construct(string $name, array $items)
    {
        parent::__construct(null, $name, 999);
        $this->items = $items;
        $this->name = $name;
    }
}
