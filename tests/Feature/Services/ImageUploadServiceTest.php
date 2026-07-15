<?php

namespace Tests\Feature\Services;

use App\Services\ImageUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    protected function fakeUploadedImage(int $width = 2000, int $height = 1500): UploadedFile
    {
        return UploadedFile::fake()->image('photo.jpg', $width, $height);
    }

    public function test_store_resizes_to_max_dimension_and_reencodes_to_webp(): void
    {
        $service = app(ImageUploadService::class);

        $path = $service->store($this->fakeUploadedImage(2000, 1500), 'test');

        Storage::disk('public')->assertExists($path);
        $this->assertStringEndsWith('.webp', $path);

        [$width, $height] = getimagesize(Storage::disk('public')->path($path));
        $this->assertLessThanOrEqual(1600, $width);
        $this->assertLessThanOrEqual(1600, $height);
    }

    public function test_store_does_not_upscale_a_smaller_image(): void
    {
        $service = app(ImageUploadService::class);

        $path = $service->store($this->fakeUploadedImage(300, 200), 'test');

        [$width, $height] = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame(300, $width);
        $this->assertSame(200, $height);
    }

    public function test_store_also_creates_a_thumbnail_variant(): void
    {
        $service = app(ImageUploadService::class);

        $path = $service->store($this->fakeUploadedImage(2000, 1500), 'test');
        $thumbPath = $service->thumbnailPath($path);

        Storage::disk('public')->assertExists($thumbPath);
        [$width, $height] = getimagesize(Storage::disk('public')->path($thumbPath));
        $this->assertLessThanOrEqual(400, $width);
        $this->assertLessThanOrEqual(400, $height);
    }

    public function test_delete_removes_both_the_full_image_and_its_thumbnail(): void
    {
        $service = app(ImageUploadService::class);
        $path = $service->store($this->fakeUploadedImage(), 'test');
        $thumbPath = $service->thumbnailPath($path);

        $service->delete($path);

        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertMissing($thumbPath);
    }

    public function test_thumbnail_url_falls_back_to_full_image_when_no_thumbnail_exists(): void
    {
        $service = app(ImageUploadService::class);
        Storage::disk('public')->put('legacy/old-image.jpg', 'not a real image, just bytes');

        $url = $service->thumbnailUrl('legacy/old-image.jpg');

        $this->assertStringContainsString('legacy/old-image.jpg', $url);
    }

    public function test_thumbnail_url_returns_external_urls_unchanged(): void
    {
        $service = app(ImageUploadService::class);

        $url = $service->thumbnailUrl('https://example.com/seeded-photo.jpg');

        $this->assertSame('https://example.com/seeded-photo.jpg', $url);
    }

    public function test_reprocess_existing_converts_a_legacy_image_and_removes_the_original(): void
    {
        $service = app(ImageUploadService::class);
        $legacyPath = 'legacy/old-image.jpg';
        $source = $this->fakeUploadedImage(2000, 1500);
        Storage::disk('public')->put($legacyPath, file_get_contents($source->getRealPath()));

        $newPath = $service->reprocessExisting($legacyPath, 'legacy');

        $this->assertStringEndsWith('.webp', $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertExists($service->thumbnailPath($newPath));
        Storage::disk('public')->assertMissing($legacyPath);
    }
}
