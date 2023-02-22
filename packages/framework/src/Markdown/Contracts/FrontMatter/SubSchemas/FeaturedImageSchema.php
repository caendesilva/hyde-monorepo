<?php

declare(strict_types=1);

namespace Hyde\Markdown\Contracts\FrontMatter\SubSchemas;

/**
 * @see \Hyde\Framework\Features\Blogging\Models\FeaturedImage
 * @see \Hyde\Pages\MarkdownPost
 */
interface FeaturedImageSchema
{
    public const FEATURED_IMAGE_SCHEMA = [
        'source'         => 'string',
        'path'           => 'string', // @deprecated
        'url'            => 'string', // @deprecated
        'description'    => 'string',
        'title'          => 'string',
        'copyright'      => 'string',
        'license'        => 'string',
        'licenseUrl'     => 'string',
        'author'         => 'string',
        'attributionUrl' => 'string',
    ];
}
