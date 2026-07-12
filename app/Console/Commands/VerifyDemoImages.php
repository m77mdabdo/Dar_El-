<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Verifies every image reference in the catalog actually exists on disk
 * (storage/app/public) and reports any broken paths, duplicate reuse, and
 * products missing a cover or gallery photo. Read-only — fixes nothing,
 * just reports, so it's safe to run anytime as a sanity check.
 */
class VerifyDemoImages extends Command
{
    protected $signature = 'demo:verify-images';

    protected $description = 'Check every product/category/brand/collection/banner/blog image path exists on disk';

    public function handle(): int
    {
        $broken = collect();
        $seen = [];
        $duplicates = collect();

        $checks = [
            'Product cover' => Product::pluck('image_url', 'id'),
            'Category image' => Category::pluck('image', 'id'),
            'Brand logo' => Brand::pluck('logo', 'id'),
            'Brand banner' => Brand::pluck('banner', 'id'),
            'Collection image' => Collection::pluck('image', 'id'),
            'Banner image' => Banner::pluck('image', 'id'),
            'Blog cover' => BlogPost::pluck('cover_image', 'id'),
        ];

        foreach ($checks as $label => $pairs) {
            foreach ($pairs as $id => $path) {
                $this->checkPath($label, $id, $path, $broken, $seen, $duplicates);
            }
        }

        foreach (ProductImage::pluck('path', 'id') as $id => $path) {
            $this->checkPath('Product gallery image', $id, $path, $broken, $seen, $duplicates);
        }

        $productsWithoutImages = Product::doesntHave('images')->count();
        $productsWithoutCover = Product::whereNull('image_url')->count();

        $this->info('Checked '.array_sum(array_map(fn ($p) => $p->count(), $checks)).' single-image fields + '.ProductImage::count().' gallery images.');

        if ($broken->isNotEmpty()) {
            $this->error("Found {$broken->count()} broken image reference(s):");
            $broken->each(fn ($line) => $this->line("  - {$line}"));
        } else {
            $this->info('No broken image references found.');
        }

        if ($duplicates->isNotEmpty()) {
            $this->warn("Found {$duplicates->count()} image path(s) reused across multiple records:");
            $duplicates->each(fn ($line) => $this->line("  - {$line}"));
        } else {
            $this->info('No duplicate image reuse found.');
        }

        if ($productsWithoutImages > 0 || $productsWithoutCover > 0) {
            $this->error("{$productsWithoutCover} product(s) missing a cover image, {$productsWithoutImages} missing gallery images.");
        } else {
            $this->info('Every product has a cover image and at least one gallery image.');
        }

        return ($broken->isEmpty() && $productsWithoutImages === 0 && $productsWithoutCover === 0) ? self::SUCCESS : self::FAILURE;
    }

    protected function checkPath(string $label, int $id, ?string $path, $broken, array &$seen, $duplicates): void
    {
        if (! $path) {
            return;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return; // legacy external URLs are out of scope for this check
        }

        if (! Storage::disk('public')->exists($path)) {
            $broken->push("{$label} #{$id}: {$path}");
        }

        $key = $label.'::'.$path;

        if (isset($seen[$key])) {
            $duplicates->push("{$label}: {$path} (used by #{$seen[$key]} and #{$id})");
        } else {
            $seen[$key] = $id;
        }
    }
}
