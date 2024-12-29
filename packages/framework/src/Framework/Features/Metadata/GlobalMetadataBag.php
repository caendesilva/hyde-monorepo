<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Metadata;

use Hyde\Hyde;
use Hyde\Facades\Meta;
use Hyde\Facades\Config;
use Hyde\Facades\Features;
use Hyde\Pages\Concerns\HydePage;
use Hyde\Support\Facades\Render;
use Hyde\Framework\Features\XmlGenerators\RssFeedGenerator;
use Hyde\Framework\Features\Metadata\MetadataElementContract as Element;

use function array_filter;
use function array_map;
use function in_array;

class GlobalMetadataBag extends MetadataBag
{
    public static function make(): static
    {
        $metadata = new static();

        /** @var MetadataElementContract $item */
        foreach (Config::getArray('hyde.meta', []) as $item) {
            $metadata->add($item);
        }

        if (Features::hasSitemap()) {
            $metadata->add(Meta::link('sitemap', Hyde::url('sitemap.xml'), [
                'type' => 'application/xml', 'title' => 'Sitemap',
            ]));
        }

        if (Features::hasRss()) {
            $metadata->add(Meta::link('alternate', Hyde::url(RssFeedGenerator::getFilename()), [
                'type' => 'application/rss+xml', 'title' => RssFeedGenerator::getDescription(),
            ]));
        }

        if (Render::getPage() !== null) {
            static::filterDuplicateMetadata($metadata, Render::getPage());
        }

        return $metadata;
    }

    protected static function filterDuplicateMetadata(GlobalMetadataBag $global, HydePage $page): void
    {
        // Reject any metadata from the global metadata bag that is already present in the page metadata bag.

        foreach (['links', 'metadata', 'properties', 'generics'] as $type) {
            $global->$type = array_filter((array) $global->$type, fn (Element $element): bool => ! in_array($element->uniqueKey(),
                array_map(fn (Element $element): string => $element->uniqueKey(), (array) $page->metadata->$type)
            ));
        }
    }
}
