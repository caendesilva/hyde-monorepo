<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\BuildTasks;

use RuntimeException;

class BuildTaskSkippedException extends RuntimeException
{
    public function __construct(string $message = 'Task was skipped', int $code = 3)
    {
        parent::__construct($message, $code);
    }
}
