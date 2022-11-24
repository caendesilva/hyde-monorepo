<?php

declare(strict_types=1);

namespace Hyde\Framework\Actions\Concerns;

use Hyde\Hyde;
use function file_exists;
use Hyde\Framework\Actions\Contracts\CreateActionContract;
use Hyde\Framework\Exceptions\FileConflictException;

/**
 * @see \Hyde\Framework\Testing\Feature\CreateActionTest
 */
abstract class CreateAction implements CreateActionContract
{
    protected string $outputPath;
    protected bool $force = false;

    abstract protected function handleCreate(): void;

    /** @inheritDoc */
    public function create(): void
    {
        if ($this->fileConflicts()) {
            throw new FileConflictException($this->outputPath);
        }

        $this->handleCreate();
    }

    /** @inheritDoc */
    public function force(bool $force = true): static
    {
        $this->force = $force;

        return $this;
    }

    /** @inheritDoc */
    public function setOutputPath(string $outputPath): static
    {
        $this->outputPath = $outputPath;

        return $this;
    }

    /** @inheritDoc */
    public function getOutputPath(): string
    {
        return $this->outputPath;
    }

    /** @inheritDoc */
    public function getAbsoluteOutputPath(): string
    {
        return Hyde::path($this->getOutputPath());
    }

    /** @inheritDoc */
    public function fileExists(): bool
    {
        return file_exists($this->getAbsoluteOutputPath());
    }

    /** @inheritDoc */
    public function fileConflicts(): bool
    {
        return $this->fileExists() && ! $this->force;
    }

    protected function filePutContents(string $contents): void
    {
        file_put_contents($this->getAbsoluteOutputPath(), $contents);
    }
}
