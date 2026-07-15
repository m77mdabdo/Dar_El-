<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class ImageUploadService
{
    /** Longest-side cap for the stored full-size image. */
    private const MAX_DIMENSION = 1600;

    /** Longest-side cap for the list/grid thumbnail variant. */
    private const THUMBNAIL_DIMENSION = 400;

    private const WEBP_QUALITY = 82;

    private ImageManager $imageManager;

    public function __construct()
    {
        // GD, not Imagick — the only extension guaranteed present on the
        // production host (Hostinger shared hosting); Imagick isn't always
        // enabled there.
        $this->imageManager = ImageManager::gd();
    }

    /**
     * Store a new uploaded image under the given directory and return its relative path.
     * Resizes to a max ~1600px on the longest side and re-encodes to WebP,
     * and stores a separate, smaller thumbnail variant (see thumbnailUrl())
     * for list/grid views. Non-image files (nothing currently reaches this
     * method without passing an `image` validation rule) fall back to a
     * plain, unprocessed store so a corrupt/unrecognized upload can't 500.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $directory = trim($directory, '/');
        $path = $directory.'/'.Str::random(32).'.webp';

        try {
            $full = $this->imageManager->read($file->getRealPath())
                ->scaleDown(width: self::MAX_DIMENSION, height: self::MAX_DIMENSION);
            Storage::disk('public')->put($path, (string) $full->toWebp(self::WEBP_QUALITY));

            $thumb = $this->imageManager->read($file->getRealPath())
                ->scaleDown(width: self::THUMBNAIL_DIMENSION, height: self::THUMBNAIL_DIMENSION);
            Storage::disk('public')->put($this->thumbnailPath($path), (string) $thumb->toWebp(self::WEBP_QUALITY));
        } catch (\Throwable) {
            // Decode failure on a file that passed the `image` validation
            // rule is not expected in practice, but must never block the
            // admin's save — fall back to storing the original untouched.
            $path = $file->store($directory, 'public');
        }

        return $path;
    }

    /**
     * Relative path of a given image's thumbnail variant, in a sibling
     * `thumbs/` directory next to the full-size file. Does not check
     * whether the thumbnail actually exists on disk — use thumbnailUrl()
     * for a safe, fallback-aware public URL.
     */
    public function thumbnailPath(?string $path): ?string
    {
        if (! $path || Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $directory = dirname($path);
        $filename = basename($path);

        return ($directory === '.' ? '' : "{$directory}/").'thumbs/'.$filename;
    }

    /**
     * Public URL for an image's thumbnail variant, falling back to the
     * full-size image's own URL when no thumbnail exists — covers external
     * URLs (legacy seeded links) and images uploaded before this thumbnail
     * feature shipped, neither of which have a `thumbs/` sibling on disk.
     */
    public function thumbnailUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $thumbPath = $this->thumbnailPath($path);

        return Storage::disk('public')->exists($thumbPath)
            ? Storage::disk('public')->url($thumbPath)
            : Storage::disk('public')->url($path);
    }

    /**
     * Delete the previous image (if any) and store the replacement, returning its relative path.
     */
    public function replace(?string $oldPath, UploadedFile $file, string $directory): string
    {
        $this->delete($oldPath);

        return $this->store($file, $directory);
    }

    /**
     * Delete a stored image by its relative path. Leaves external URLs (e.g. legacy
     * seeded links) untouched since they aren't files on our local disk.
     */
    public function delete(?string $path): void
    {
        if (! $path || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        Storage::disk('public')->delete($path);
        Storage::disk('public')->delete($this->thumbnailPath($path));
    }

    /**
     * Copy an existing stored image to a new path under the given directory,
     * returning the new relative path. Used when duplicating records that
     * reference the same image — copies the actual file so deleting one
     * copy never orphans the other. External URLs and missing files are
     * returned unchanged (nothing local to copy).
     */
    public function copy(?string $path, string $directory): ?string
    {
        if (! $path || Str::startsWith($path, ['http://', 'https://']) || ! Storage::disk('public')->exists($path)) {
            return $path;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $newPath = trim($directory, '/').'/'.Str::random(32).($extension ? ".{$extension}" : '');

        Storage::disk('public')->put($newPath, Storage::disk('public')->get($path));

        $oldThumb = $this->thumbnailPath($path);
        if (Storage::disk('public')->exists($oldThumb)) {
            Storage::disk('public')->put($this->thumbnailPath($newPath), Storage::disk('public')->get($oldThumb));
        }

        return $newPath;
    }

    /**
     * Re-encode an already-stored image (uploaded before the resize/WebP/
     * thumbnail behavior above shipped) to the same resized WebP + thumbnail
     * format `store()` now produces for new uploads, under the given
     * directory. Returns the new relative path; the old file(s) are
     * deleted. Used only by the `images:backfill-thumbnails` command — not
     * called anywhere in the normal upload/replace/delete flow.
     */
    public function reprocessExisting(string $existingPath, string $directory): string
    {
        $directory = trim($directory, '/');
        $newPath = $directory.'/'.Str::random(32).'.webp';

        $contents = Storage::disk('public')->get($existingPath);

        $full = $this->imageManager->read($contents)
            ->scaleDown(width: self::MAX_DIMENSION, height: self::MAX_DIMENSION);
        Storage::disk('public')->put($newPath, (string) $full->toWebp(self::WEBP_QUALITY));

        $thumb = $this->imageManager->read($contents)
            ->scaleDown(width: self::THUMBNAIL_DIMENSION, height: self::THUMBNAIL_DIMENSION);
        Storage::disk('public')->put($this->thumbnailPath($newPath), (string) $thumb->toWebp(self::WEBP_QUALITY));

        $this->delete($existingPath);

        return $newPath;
    }
}
