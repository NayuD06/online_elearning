<?php

namespace App\Filesystem;

use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Cloudinary;
use GuzzleHttp\Psr7\Utils;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use Throwable;

/**
 * Flysystem adapter that keeps uploaded files on Cloudinary, so they survive redeploys.
 *
 * A path such as "avatars/abc.jpg" becomes the public ID "<folder>/avatars/abc". Images and
 * videos are stored without their extension (Cloudinary adds it back in the URL); any other
 * file is stored as a "raw" asset whose public ID keeps the extension.
 */
class CloudinaryAdapter implements FilesystemAdapter
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'svg', 'ico', 'tif', 'tiff', 'heic'];

    private const VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov', 'm4v', 'ogv', 'avi', 'mkv', 'mp3', 'wav', 'ogg', 'm4a'];

    private PathPrefixer $prefixer;

    public function __construct(private Cloudinary $cloudinary, string $folder = '')
    {
        $this->prefixer = new PathPrefixer($folder);
    }

    /**
     * Public delivery URL of a file, used by Storage::disk(...)->url().
     */
    public function getUrl(string $path): string
    {
        $type = $this->resourceType($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $url = sprintf('https://res.cloudinary.com/%s/%s/upload/', $this->cloudinary->configuration->cloud->cloudName, $type);

        // Let Cloudinary pick the lightest format and quality the browser supports.
        if ($type === 'image' && $extension !== 'svg') {
            $url .= 'f_auto,q_auto/';
        }

        return $url.$this->publicId($path).($type === 'raw' ? '' : '.'.$extension);
    }

    public function fileExists(string $path): bool
    {
        try {
            $this->metadata($path);

            return true;
        } catch (NotFound) {
            return false;
        }
    }

    public function directoryExists(string $path): bool
    {
        foreach (['image', 'video', 'raw'] as $type) {
            $assets = $this->cloudinary->adminApi()->assets([
                'resource_type' => $type,
                'type' => 'upload',
                'prefix' => $this->prefixer->prefixDirectoryPath($path),
                'max_results' => 1,
            ]);

            if (! empty($assets['resources'])) {
                return true;
            }
        }

        return false;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->upload($path, Utils::streamFor($contents));
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->upload($path, Utils::streamFor($contents));
    }

    public function read(string $path): string
    {
        $contents = @file_get_contents($this->originalUrl($path));

        if ($contents === false) {
            throw UnableToReadFile::fromLocation($path, error_get_last()['message'] ?? '');
        }

        return $contents;
    }

    public function readStream(string $path)
    {
        $stream = @fopen($this->originalUrl($path), 'rb');

        if ($stream === false) {
            throw UnableToReadFile::fromLocation($path, error_get_last()['message'] ?? '');
        }

        return $stream;
    }

    public function delete(string $path): void
    {
        try {
            $this->cloudinary->uploadApi()->destroy($this->publicId($path), [
                'resource_type' => $this->resourceType($path),
                'invalidate' => true,
            ]);
        } catch (Throwable $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            foreach (['image', 'video', 'raw'] as $type) {
                $this->cloudinary->adminApi()->deleteAssetsByPrefix(
                    $this->prefixer->prefixDirectoryPath($path),
                    ['resource_type' => $type],
                );
            }
        } catch (Throwable $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        // Cloudinary creates folders on its own when a file is uploaded into them.
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // Every uploaded file is public.
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, null, Visibility::PUBLIC);
    }

    public function mimeType(string $path): FileAttributes
    {
        $mimeType = (new ExtensionMimeTypeDetector)->detectMimeTypeFromPath($path);

        if ($mimeType === null) {
            throw UnableToRetrieveMetadata::mimeType($path);
        }

        return new FileAttributes($path, null, null, null, $mimeType);
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->fileAttributes($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->fileAttributes($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $prefix = $this->prefixer->prefixDirectoryPath($path);

        foreach (['image', 'video', 'raw'] as $type) {
            $cursor = null;

            do {
                $page = $this->cloudinary->adminApi()->assets(array_filter([
                    'resource_type' => $type,
                    'type' => 'upload',
                    'prefix' => $prefix,
                    'max_results' => 500,
                    'next_cursor' => $cursor,
                ]));

                foreach ($page['resources'] ?? [] as $asset) {
                    $file = $this->prefixer->stripPrefix($asset['public_id'].($type === 'raw' ? '' : '.'.$asset['format']));
                    $relative = substr($file, strlen(trim($path, '/')) + ($path === '' ? 0 : 1));

                    if (! $deep && str_contains($relative, '/')) {
                        yield new DirectoryAttributes(rtrim($path, '/').'/'.strtok($relative, '/'));

                        continue;
                    }

                    yield new FileAttributes($file, $asset['bytes'] ?? null, Visibility::PUBLIC, strtotime($asset['created_at'] ?? 'now'));
                }

                $cursor = $page['next_cursor'] ?? null;
            } while ($cursor);
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        if ($this->resourceType($source) !== $this->resourceType($destination)) {
            throw UnableToMoveFile::fromLocationTo($source, $destination);
        }

        try {
            $this->cloudinary->uploadApi()->rename($this->publicId($source), $this->publicId($destination), [
                'resource_type' => $this->resourceType($source),
                'overwrite' => true,
                'invalidate' => true,
            ]);
        } catch (Throwable $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            // Cloudinary fetches the source file itself when given its URL.
            $this->cloudinary->uploadApi()->upload($this->originalUrl($source), $this->uploadOptions($destination));
        } catch (Throwable $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    private function upload(string $path, mixed $stream): void
    {
        try {
            $this->cloudinary->uploadApi()->upload($stream, $this->uploadOptions($path));
        } catch (Throwable $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function uploadOptions(string $path): array
    {
        $publicId = $this->publicId($path);

        return [
            'public_id' => $publicId,
            'resource_type' => $this->resourceType($path),
            'asset_folder' => dirname($publicId) === '.' ? '' : dirname($publicId),
            'filename' => basename($path),
            'overwrite' => true,
            'invalidate' => true,
        ];
    }

    private function fileAttributes(string $path): FileAttributes
    {
        try {
            $asset = $this->metadata($path);
        } catch (Throwable $e) {
            throw UnableToRetrieveMetadata::fileSize($path, $e->getMessage(), $e);
        }

        return new FileAttributes($path, $asset['bytes'] ?? null, Visibility::PUBLIC, strtotime($asset['created_at'] ?? 'now'));
    }

    private function metadata(string $path): ApiResponse
    {
        return $this->cloudinary->adminApi()->asset($this->publicId($path), [
            'resource_type' => $this->resourceType($path),
            'type' => 'upload',
        ]);
    }

    /**
     * URL of the file exactly as uploaded, without the automatic format/quality transformation.
     */
    private function originalUrl(string $path): string
    {
        return str_replace('/upload/f_auto,q_auto/', '/upload/', $this->getUrl($path));
    }

    private function publicId(string $path): string
    {
        $path = $this->prefixer->prefixPath(ltrim($path, '/'));

        if ($this->resourceType($path) === 'raw') {
            return $path;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension === '' ? $path : substr($path, 0, -strlen($extension) - 1);
    }

    private function resourceType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match (true) {
            in_array($extension, self::IMAGE_EXTENSIONS, true) => 'image',
            in_array($extension, self::VIDEO_EXTENSIONS, true) => 'video',
            default => 'raw',
        };
    }
}
