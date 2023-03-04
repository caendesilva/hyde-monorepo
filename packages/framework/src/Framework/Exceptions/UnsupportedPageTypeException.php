<?php

declare(strict_types=1);

namespace Hyde\Framework\Exceptions;

use Exception;

class UnsupportedPageTypeException extends Exception
{
    /** @var string */
    protected $message = 'The page type is not supported.';

    /** @var int */
    protected $code = 400;

    public function __construct(?string $page = null)
    {
        $message = $page ? "The page type [$page] is not supported." : $this->message;

        parent::__construct($message);
    }
}
