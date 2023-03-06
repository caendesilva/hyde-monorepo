<?php

declare(strict_types=1);

namespace Hyde\Pages\Concerns;

use Stringable;
use Illuminate\Contracts\Support\Arrayable;

class PageContents implements Arrayable, Stringable
{
    public string $body;

    public function __construct(string $body = '')
    {
        $this->body = str_replace("\r\n", "\n", rtrim($body));
    }

    public function __toString(): string
    {
        return $this->body;
    }

    /** @return string[] */
    public function toArray(): array
    {
        return explode("\n", $this->body);
    }
}
