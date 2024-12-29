<?php

declare(strict_types=1);

namespace Hyde\Support\Filesystem;

use Hyde\Facades\Filesystem;
use Hyde\Hyde;
use Stringable;
use Hyde\Facades\Config;
use Illuminate\Support\Collection;
use Hyde\Framework\Exceptions\FileNotFoundException;
use Illuminate\Support\Str;

use function Hyde\unslash;
use function Hyde\path_join;
use function Hyde\trim_slashes;
use function array_merge;

/**
 * File abstraction for a project media file.
 *
 * All input paths are relative to the project's media source directory.
 */
class MediaFile extends ProjectFile implements Stringable
{
    /** @var array<string> The default extensions for media types */
    final public const EXTENSIONS = ['png', 'svg', 'jpg', 'jpeg', 'webp', 'gif', 'ico', 'css', 'js'];

    protected readonly int $length;
    protected readonly string $mimeType;
    protected readonly string $hash;

    /**
     * Create a new MediaFile instance.
     *
     * @param  string  $path  The file path relative to the project root or media source directory.
     *
     * @throws \Hyde\Framework\Exceptions\FileNotFoundException If the file does not exist in the media source directory.
     */
    public function __construct(string $path)
    {
        parent::__construct($this->getNormalizedPath($path));
    }

    /**
     * Cast the instance to a string which is the resolved web link to the media file.
     */
    public function __toString(): string
    {
        return $this->getLink();
    }

    /**
     * Create a media file instance for the given file.
     */
    public static function make(string $path): static
    {
        return parent::make($path);
    }

    /**
     * Get or create a media file instance from the HydeKernel for the given file.
     *
     * @throws \Hyde\Framework\Exceptions\FileNotFoundException If the file does not exist in the `_media` source directory.
     */
    public static function get(string $path): MediaFile
    {
        return Hyde::assets()->get($path) ?? static::make($path);
    }

    /**
     * Get a collection of all media files, parsed into `MediaFile` instances, keyed by the filenames relative to the `_media/` directory.
     *
     * @return \Illuminate\Support\Collection<string, \Hyde\Support\Filesystem\MediaFile>
     */
    public static function all(): Collection
    {
        return Hyde::assets();
    }

    /**
     * Get an array of media asset filenames relative to the `_media/` directory.
     *
     * @return array<int, string> {@example `['app.css', 'images/logo.svg']`}
     */
    public static function files(): array
    {
        return static::all()->keys()->all();
    }

    /**
     * Get the absolute path to the media source directory, or a file within it.
     */
    public static function sourcePath(string $path = ''): string
    {
        if (empty($path)) {
            return Hyde::path(Hyde::getMediaDirectory());
        }

        return Hyde::path(path_join(Hyde::getMediaDirectory(), unslash($path)));
    }

    /**
     * Get the absolute path to the compiled site's media directory, or a file within it.
     */
    public static function outputPath(string $path = ''): string
    {
        if (empty($path)) {
            return Hyde::sitePath(Hyde::getMediaOutputDirectory());
        }

        return Hyde::sitePath(path_join(Hyde::getMediaOutputDirectory(), unslash($path)));
    }

    /**
     * Get the path to the media file relative to the media directory.
     */
    public function getIdentifier(): string
    {
        return Str::after($this->getPath(), Hyde::getMediaDirectory().'/');
    }

    /**
     * Get a relative web link to the media file.
     */
    public function getLink(): string
    {
        $name = $this->getIdentifier();

        $name = Str::start($name, Hyde::getMediaOutputDirectory().'/');

        if (Hyde::hasSiteUrl()) {
            return Hyde::url($name).$this->getCacheBustKey();
        }

        return Hyde::relativeLink($name).$this->getCacheBustKey();
    }

    /**
     * Get the absolute path to the media file in the compiled site.
     */
    public function getOutputPath(): string
    {
        return static::outputPath($this->getIdentifier());
    }

    /**
     * Get the content length of the file in bytes.
     */
    public function getLength(): int
    {
        $this->ensureInstanceIsBooted('length');

        return $this->length;
    }

    /**
     * Get the MIME type of the file.
     */
    public function getMimeType(): string
    {
        $this->ensureInstanceIsBooted('mimeType');

        return $this->mimeType;
    }

    /**
     * Get the CRC32 hash of the file.
     */
    public function getHash(): string
    {
        $this->ensureInstanceIsBooted('hash');

        return $this->hash;
    }

    /**
     * Get the file information as an array.
     *
     * @return array{name: string, path: string, length: int, mimeType: string, hash: string}
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'length' => $this->getLength(),
            'mimeType' => $this->getMimeType(),
            'hash' => $this->getHash(),
        ]);
    }

    protected function getCacheBustKey(): string
    {
        return Config::getBool('hyde.cache_busting', true)
            ? '?v='.$this->getHash()
            : '';
    }

    protected function getNormalizedPath(string $path): string
    {
        // Ensure we are working with a relative project path
        $path = Hyde::pathToRelative($path);

        // Normalize paths using output directory to have source directory prefix
        if (str_starts_with($path, Hyde::getMediaOutputDirectory()) && str_starts_with(Hyde::getMediaDirectory(), '_')) {
            $path = '_'.$path;
        }

        // Normalize the path to include the media directory
        $path = static::sourcePath(trim_slashes(Str::after($path, Hyde::getMediaDirectory())));

        // Since assets need to exist on disk in order to be copied to the built site files we validate that the file is real here.
        if (Filesystem::missing($path)) {
            throw new FileNotFoundException($path, appendAfterPath: ' when trying to resolve a media asset.');
        }

        return $path;
    }

    protected function findLength(): int
    {
        return Filesystem::size($this->getPath());
    }

    protected function findMimeType(): string
    {
        return Filesystem::findMimeType($this->getPath());
    }

    protected function findHash(): string
    {
        return Filesystem::hash($this->getPath(), 'crc32');
    }

    protected function ensureInstanceIsBooted(string $property): void
    {
        if (! isset($this->$property)) {
            $this->boot();
        }
    }

    protected function boot(): void
    {
        $this->length = $this->findLength();
        $this->mimeType = $this->findMimeType();
        $this->hash = $this->findHash();
    }
}
