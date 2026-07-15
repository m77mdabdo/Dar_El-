<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ReviewImage;
use App\Services\ImageUploadService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Reprocesses images uploaded BEFORE the resize/WebP/thumbnail behavior in
 * ImageUploadService::store() shipped, so they get the same treatment new
 * uploads now get automatically. Deliberately NOT run automatically by
 * anything — this touches every matching row's file, so it's meant to be
 * reviewed (via --dry-run) and run manually as its own step.
 *
 * Covers: Product.image_url, ProductImage.path, Category.image,
 * BlogPost.cover_image, ReviewImage.path — the image types called out in
 * the performance audit this command follows up on. Does NOT (yet) cover
 * Banner/Collection/Brand/ProductVariant/ProductOptionValue images or user
 * avatars — extend targets() if those should be included later.
 */
class BackfillImageThumbnails extends Command
{
    protected $signature = 'images:backfill-thumbnails {--dry-run : List what would be reprocessed without changing any file}';

    protected $description = 'Reprocess already-uploaded images into the resized WebP + thumbnail format used for new uploads (products, categories, blog covers, review photos)';

    public function handle(ImageUploadService $imageUploader): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->targets() as [$model, $column, $directory]) {
            $path = $model->{$column};

            if (! $path || Str::startsWith($path, ['http://', 'https://'])) {
                $skipped++;
                continue;
            }

            $thumbPath = $imageUploader->thumbnailPath($path);
            if (Storage::disk('public')->exists($thumbPath)) {
                // Already has a thumbnail sibling — either already backfilled,
                // or uploaded after store() started producing one.
                $skipped++;
                continue;
            }

            if (! Storage::disk('public')->exists($path)) {
                $this->warn(sprintf('Missing file, skipping: %s#%d.%s (%s)', class_basename($model), $model->getKey(), $column, $path));
                $failed++;
                continue;
            }

            if ($dryRun) {
                $this->line(sprintf('Would reprocess: %s#%d.%s (%s)', class_basename($model), $model->getKey(), $column, $path));
                $processed++;
                continue;
            }

            try {
                $newPath = $imageUploader->reprocessExisting($path, $directory);
                $model->newQuery()->whereKey($model->getKey())->update([$column => $newPath]);
                $processed++;
            } catch (\Throwable $e) {
                $this->error(sprintf('Failed: %s#%d.%s — %s', class_basename($model), $model->getKey(), $column, $e->getMessage()));
                $failed++;
            }
        }

        $prefix = $dryRun ? '[dry-run] ' : '';
        $this->info("{$prefix}Done. Reprocessed: {$processed}, skipped (already done / external / no file): {$skipped}, failed: {$failed}");

        return self::SUCCESS;
    }

    /**
     * @return list<array{0: Model, 1: string, 2: string}> each entry is
     *     [model instance, image column name, upload directory to reuse]
     */
    protected function targets(): array
    {
        $targets = [];

        foreach (Product::whereNotNull('image_url')->get() as $product) {
            $targets[] = [$product, 'image_url', "products/{$product->id}"];
        }

        foreach (ProductImage::query()->get() as $image) {
            $targets[] = [$image, 'path', "products/{$image->product_id}"];
        }

        foreach (Category::whereNotNull('image')->get() as $category) {
            $targets[] = [$category, 'image', 'categories'];
        }

        foreach (BlogPost::whereNotNull('cover_image')->get() as $post) {
            $targets[] = [$post, 'cover_image', 'blog'];
        }

        foreach (ReviewImage::query()->get() as $image) {
            $targets[] = [$image, 'path', "reviews/{$image->review_id}"];
        }

        return $targets;
    }
}
