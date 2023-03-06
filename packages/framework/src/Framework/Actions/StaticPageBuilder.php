<?php

declare(strict_types=1);

namespace Hyde\Framework\Actions;

use Hyde\Hyde;
use Hyde\Framework\Concerns\InteractsWithDirectories;
use Hyde\Pages\Concerns\HydePage;
use function file_put_contents;

/**
 * Converts a Page Model into a static HTML page.
 *
 * @see \Hyde\Framework\Testing\Feature\StaticPageBuilderTest
 */
class StaticPageBuilder
{
    use InteractsWithDirectories;

    protected string $path;

    /**
     * Construct the class.
     *
     * @param  \Hyde\Pages\Concerns\HydePage  $page  the Page to compile into HTML
     * @param  bool  $selfInvoke  if set to true the class will invoke when constructed
     */
    public function __construct(protected HydePage $page, bool $selfInvoke = false)
    {
        $this->path = Hyde::sitePath($this->page->getOutputPath());

        if ($selfInvoke) {
            $this->__invoke();
        }
    }

    /**
     * Run the page builder.
     */
    public function __invoke(): string
    {
        Hyde::shareViewData($this->page);

        $this->needsParentDirectory($this->path);

        return $this->save($this->page->compile());
    }

    /**
     * Save the compiled HTML to file.
     *
     * @param  string  $contents  to save to the file
     * @return string the path to the saved file
     */
    protected function save(string $contents): string
    {
        file_put_contents($this->path, $contents);

        return $this->path;
    }
}
