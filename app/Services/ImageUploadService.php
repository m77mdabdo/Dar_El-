<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    /**
     * Store a new uploaded image under the given directory and return its relative path.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'public');
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
    }
}
