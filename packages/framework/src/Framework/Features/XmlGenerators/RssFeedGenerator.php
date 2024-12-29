<?php

/** @noinspection PhpComposerExtensionStubsInspection */
/** @noinspection XmlUnusedNamespaceDeclaration */

declare(strict_types=1);

namespace Hyde\Framework\Features\XmlGenerators;

use Hyde\Hyde;
use SimpleXMLElement;
use Hyde\Facades\Site;
use Hyde\Facades\Config;
use Hyde\Pages\MarkdownPost;
use Hyde\Facades\Filesystem;
use Hyde\Framework\Features\Blogging\Models\FeaturedImage;

use function date;
use function assert;
use function sprintf;
use function implode;

/**
 * @see https://validator.w3.org/feed/docs/rss2.html
 */
class RssFeedGenerator extends BaseXmlGenerator
{
    public function generate(): static
    {
        MarkdownPost::getLatestPosts()->each(function (MarkdownPost $post): void {
            $this->addItem($post);
        });

        return $this;
    }

    protected function constructBaseElement(): void
    {
        $this->xmlElement = new SimpleXMLElement(implode("\n", [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/" />',
        ]));

        $this->xmlElement->addChild('channel');

        $this->addBaseChannelItems();
        $this->addAtomLinkItem();
    }

    protected function addItem(MarkdownPost $post): void
    {
        $item = $this->getChannel()->addChild('item');

        $this->addChild($item, 'title', $post->title);
        $this->addChild($item, 'description', $post->description);

        $this->addDynamicItemData($item, $post);
    }

    protected function addDynamicItemData(SimpleXMLElement $item, MarkdownPost $post): void
    {
        if ($post->getCanonicalUrl() !== null) {
            $this->addChild($item, 'link', $post->getCanonicalUrl());
            $this->addChild($item, 'guid', $post->getCanonicalUrl());
        }

        if (isset($post->date)) {
            $this->addChild($item, 'pubDate', $post->date->dateTimeObject->format(DATE_RSS));
        }

        if (isset($post->author)) {
            $item->addChild('dc:creator', $post->author->name, 'http://purl.org/dc/elements/1.1/');
        }

        if (isset($post->category)) {
            $this->addChild($item, 'category', $post->category);
        }

        if (isset($post->image)) {
            $image = $item->addChild('enclosure');

            $image->addAttribute('url', Hyde::url($post->image->getSource()));
            $image->addAttribute('type', $this->getImageType($post->image));
            $image->addAttribute('length', $this->getImageLength($post->image));
        }
    }

    protected function addBaseChannelItems(): void
    {
        $channel = $this->getChannel();

        $this->addChild($channel, 'title', Site::name());
        $this->addChild($channel, 'link', Site::url());
        $this->addChild($channel, 'description', $this->getDescription());
        $this->addChild($channel, 'language', Config::getString('hyde.language', 'en'));
        $this->addChild($channel, 'generator', 'HydePHP '.Hyde::version());
        $this->addChild($channel, 'lastBuildDate', date(DATE_RSS));
    }

    protected function addAtomLinkItem(): void
    {
        $atomLink = $this->getChannel()->addChild('atom:link', namespace: 'http://www.w3.org/2005/Atom');

        $atomLink->addAttribute('href', $this->escape(Hyde::url($this->getFilename())));
        $atomLink->addAttribute('rel', 'self');
        $atomLink->addAttribute('type', 'application/rss+xml');
    }

    protected function getImageType(FeaturedImage $image): string
    {
        return Filesystem::findMimeType($image->getSource());
    }

    /** @return numeric-string */
    protected function getImageLength(FeaturedImage $image): string
    {
        return (string) $image->getContentLength();
    }

    public static function getFilename(): string
    {
        return Config::getString('hyde.rss.filename', 'feed.xml');
    }

    public static function getDescription(): string
    {
        return Config::getString('hyde.rss.description', sprintf('%s RSS Feed', Site::name()));
    }

    protected function getChannel(): SimpleXMLElement
    {
        assert($this->xmlElement->channel instanceof SimpleXMLElement);

        return $this->xmlElement->channel;
    }
}
