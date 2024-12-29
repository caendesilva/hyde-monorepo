<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Blogging\Models;

use Hyde\Hyde;
use Stringable;
use Hyde\Facades\Config;
use Illuminate\Support\Str;
use Hyde\Support\BuildWarnings;
use Illuminate\Support\Facades\Http;
use Hyde\Foundation\Kernel\Hyperlinks;
use Hyde\Support\Filesystem\MediaFile;
use Hyde\Markdown\Contracts\FrontMatter\SubSchemas\FeaturedImageSchema;

use function array_key_exists;
use function array_flip;
use function key;

/**
 * Object representation of a blog post's featured image.
 *
 * While the object can of course be used for any other page type,
 * it is named "FeaturedImage" as it's only usage within Hyde
 * is for the featured image of a Markdown blog post.
 *
 * @see \Hyde\Framework\Factories\FeaturedImageFactory
 */
class FeaturedImage implements Stringable, FeaturedImageSchema
{
    /**
     * A featured image object, for a file stored locally.
     *
     * The internal data structure forces the image source to reference a file in the _media directory,
     * and thus that is what is required for the input. However, when outputting data, the data will
     * be used for the _site/media directory, so it will provide data relative to the site root.
     *
     * The source information is stored in $this->source, which is a file in the _media directory.
     */
    protected final const TYPE_LOCAL = 'local';

    /**
     * A featured image object, for a file stored remotely.
     */
    protected final const TYPE_REMOTE = 'remote';

    /** @var self::TYPE_* */
    protected readonly string $type;

    protected readonly string $source;

    public function __construct(
        string $source,
        protected readonly ?string $altText = null,
        protected readonly ?string $titleText = null,
        protected readonly ?string $authorName = null,
        protected readonly ?string $authorUrl = null,
        protected readonly ?string $licenseName = null,
        protected readonly ?string $licenseUrl = null,
        protected readonly ?string $copyrightText = null
    ) {
        $this->type = Hyperlinks::isRemote($source) ? self::TYPE_REMOTE : self::TYPE_LOCAL;
        $this->source = $this->setSource($source);
    }

    public function __toString(): string
    {
        return $this->getSource();
    }

    /**
     * Get the source of the image, must be usable within the src attribute of an image tag,
     * and is thus not necessarily the path to the source image on disk.
     *
     * @return string The image's url or path
     */
    public function getSource(): string
    {
        if ($this->type === self::TYPE_LOCAL) {
            // Return value is always resolvable from a compiled page in the _site directory.
            return (string) Hyde::asset($this->source);
        }

        return $this->source;
    }

    protected function setSource(string $source): string
    {
        if ($this->type === self::TYPE_LOCAL) {
            // Normalize away any leading media path prefixes.
            return Str::after($source, Hyde::getMediaDirectory().'/');
        }

        return $source;
    }

    public function getContentLength(): int
    {
        if ($this->type === self::TYPE_LOCAL) {
            return $this->getContentLengthForLocalImage();
        }

        return $this->getContentLengthForRemoteImage();
    }

    /** @return self::TYPE_* */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Used in resources/views/components/post/image.blade.php to add meta tags with itemprop attributes.
     *
     * @return array{text?: string|null, name?: string|null, url: string, contentUrl: string}
     */
    public function getMetadataArray(): array
    {
        $metadata = [];

        if ($this->hasAltText()) {
            $metadata['text'] = $this->getAltText();
        }

        if ($this->hasTitleText()) {
            $metadata['name'] = $this->getTitleText();
        }

        $metadata['url'] = $this->getSource();
        $metadata['contentUrl'] = $this->getSource();

        return $metadata;
    }

    public function getAltText(): ?string
    {
        return $this->altText;
    }

    public function getTitleText(): ?string
    {
        return $this->titleText;
    }

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function getAuthorUrl(): ?string
    {
        return $this->authorUrl;
    }

    public function getCopyrightText(): ?string
    {
        return $this->copyrightText;
    }

    public function getLicenseName(): ?string
    {
        return $this->licenseName;
    }

    public function getLicenseUrl(): ?string
    {
        return $this->licenseUrl;
    }

    public function hasAltText(): bool
    {
        return $this->has('altText');
    }

    public function hasTitleText(): bool
    {
        return $this->has('titleText');
    }

    public function hasAuthorName(): bool
    {
        return $this->has('authorName');
    }

    public function hasAuthorUrl(): bool
    {
        return $this->has('authorUrl');
    }

    public function hasCopyrightText(): bool
    {
        return $this->has('copyrightText');
    }

    public function hasLicenseName(): bool
    {
        return $this->has('licenseName');
    }

    public function hasLicenseUrl(): bool
    {
        return $this->has('licenseUrl');
    }

    protected function has(string $property): bool
    {
        return $this->$property !== null;
    }

    protected function getContentLengthForLocalImage(): int
    {
        return MediaFile::get($this->source)->getLength();
    }

    protected function getContentLengthForRemoteImage(): int
    {
        // API calls can be skipped in the config, or by setting the --no-api flag when running the build command.
        if (Config::getBool('hyde.api_calls', true)) {
            /** @var string[][] $headers */
            $headers = Http::withHeaders([
                'User-Agent' => Config::getString('hyde.http_user_agent', 'RSS Request Client'),
            ])->head($this->getSource())->headers();

            if (array_key_exists('Content-Length', $headers)) {
                return (int) key(array_flip($headers['Content-Length']));
            }

            BuildWarnings::report('The image "'.$this->getSource().'" has a content length of zero.');
        }

        return 0;
    }
}
