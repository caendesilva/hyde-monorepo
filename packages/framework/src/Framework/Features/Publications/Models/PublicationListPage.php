<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Publications\Models;

use Hyde\Framework\Actions\PublicationPageCompiler;
use Hyde\Pages\BladePage;
use Hyde\Pages\Contracts\DynamicPage;

/**
 * FIXME refactor to dynamic page
 
 * @see \Hyde\Pages\PublicationPage
 * @see \Hyde\Framework\Testing\Feature\PublicationListPageTest
 */
class PublicationListPage extends BladePage implements DynamicPage
{
    public static string $sourceDirectory = '__publications';
    public static string $outputDirectory = '';
    public static string $fileExtension = 'json';

    public PublicationType $type;

    public function __construct(PublicationType $type)
    {
        $this->type = $type;

        parent::__construct("{$type->getDirectory()}/index");
    }

    public function compile(): string
    {
        return PublicationPageCompiler::call($this);
    }

    public function getSourcePath(): string
    {
        return $this->type->getDirectory().'/schema.json';
    }
}
