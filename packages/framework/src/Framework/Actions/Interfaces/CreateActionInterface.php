<?php

namespace Hyde\Framework\Actions\Interfaces;

interface CreateActionInterface
{
    public function create(): string|bool;
}