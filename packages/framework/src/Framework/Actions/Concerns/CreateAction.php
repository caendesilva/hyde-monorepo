<?php

declare(strict_types=1);

namespace Hyde\Framework\Actions\Concerns;

use Hyde\Framework\Actions\Contracts\CreateActionContract;

use function file_exists;

/**
 * @see \Hyde\Framework\Testing\Feature\CreateActionTest
 */
abstract class CreateAction implements CreateActionContract
{
    protected string $outputPath;
    protected bool $force = false;

    /** @inheritDoc */
    public function create(): void
    {
        // TODO: Implement create() method.
    }

    /** @inheritDoc */
    public function getOutputPath(): string
    {
        return $this->outputPath;
    }

    /** @inheritDoc */
    public function setOutputPath(string $outputPath): static
    {
        $this->outputPath = $outputPath;
        return $this;
    }

    /** @inheritDoc */
    public function force(bool $force = true): static
    {
        $this->force = $force;
        return $this;
    }

    /** @inheritDoc */
    public function fileExists(): bool
    {
        return file_exists($this->outputPath);
    }

    /** @inheritDoc */
    public function fileConflicts(): bool
    {
        return file_exists($this->outputPath) && ! $this->force;
    }
}
