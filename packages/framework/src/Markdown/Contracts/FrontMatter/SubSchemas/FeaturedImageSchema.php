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
        'source'         => 'string', // Filename in _media/ or a remote URL
        'description'    => 'string',
        'title'          => 'string',
        'copyright'      => 'string',
        'licenseName'    => 'string',
        'licenseUrl'     => 'string',
        'authorName'     => 'string',
        'authorUrl' => 'string',
    ];
}
