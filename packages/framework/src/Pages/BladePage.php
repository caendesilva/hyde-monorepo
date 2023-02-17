<?php

declare(strict_types=1);

namespace Hyde\Pages;

use Hyde\Markdown\Models\FrontMatter;
use Hyde\Pages\Concerns\Discoverable;
use Hyde\Pages\Concerns\HydePage;
use Hyde\Support\Contracts\DiscoverableContract;
use Illuminate\Support\Facades\View;

/**
 * Page class for Blade pages.
 *
 * Blade pages are stored in the _pages directory and using the .blade.php extension.
 * They will be compiled using the Laravel Blade engine the _site/ directory.
 *
 * @see https://hydephp.com/docs/master/static-pages#creating-blade-pages
 * @see https://laravel.com/docs/master/blade
 */
class BladePage extends HydePage implements DiscoverableContract
{
    use Discoverable;

    protected static string $sourceDirectory = '_pages';
    protected static string $outputDirectory = '';
    protected static string $fileExtension = '.blade.php';

    /**
     * The name of the Blade View to compile. Commonly stored in _pages/{$identifier}.blade.php.
     */
    public string $view;

    public function __construct(string $view = '', FrontMatter|array $matter = [])
    {
        parent::__construct($view, $matter);
        $this->view = $view;
    }

    /** @inheritDoc */
    public function getBladeView(): string
    {
        return $this->view;
    }

    /** @inheritDoc */
    public function compile(): string
    {
        return View::make($this->getBladeView())->render();
    }
}
