<?php

declare(strict_types=1);

/**
 * @internal
 */
class HydeStan
{
    protected array $errors = [];
    protected array $files;
    protected Console $console;

    public function __construct()
    {
        $this->console = new Console();

        $this->console->info('HydeStan is running...');
    }

    public function __destruct()
    {
        $this->console->info('HydeStan has finished.');
    }

    public function run(): void
    {
        $this->files = $this->getFiles();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function getFiles(): array
    {
        $files = [];

        $directory = new RecursiveDirectoryIterator(BASE_PATH . '/src');
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

        foreach ($regex as $file) {
            $files[] = substr($file[0], strlen(BASE_PATH) + 1);
        }

        return $files;
    }
}
