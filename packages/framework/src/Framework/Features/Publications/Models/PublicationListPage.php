<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Publications\Models;

use Hyde\Framework\Actions\PublicationPageCompiler;
use Hyde\Pages\VirtualPage;

/**
 * @see \Hyde\Pages\PublicationPage
 * @see \Hyde\Framework\Testing\Feature\PublicationListPageTest
 */
class PublicationListPage extends VirtualPage
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

    /** @deprecated for this child class */
    public function getSourcePath(): string
    {
        return $this->type->getDirectory().'/schema.json';
    }
}
