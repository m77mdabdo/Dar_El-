<?php

namespace App\Console\Commands;

use App\Services\DemoImageImporter;
use App\Services\DemoImageManifest;
use Database\Seeders\DemoCategorySeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/**
 * The single entry point requested for the demo catalog: `php artisan
 * demo:import`. Ensures the public storage symlink exists, downloads every
 * real photo the demo seeders need (via Pexels, cached in a manifest so
 * re-runs are free unless --force is passed), then runs the seeders in
 * dependency order (brands/collections/categories before products, which
 * need all three; blog and banners last).
 */
class ImportDemoData extends Command
{
    protected $signature = 'demo:import {--force : Re-download every image bucket even if already cached} {--images-only : Only download images, do not run the seeders}';

    protected $description = 'Populate the store with a full demo catalog: brands, categories, collections, products, variants, blog posts, reviews, and homepage banners — all with real downloaded photos';

    protected const PRODUCTS_PER_ITEM = 6; // 1 cover + 5 gallery, matches DemoProductSeeder

    protected const BRAND_COUNT = 45;

    protected const BLOG_COUNT = 50;

    /**
     * DemoBannerSeeder creates 23 banner rows (4 hero + 3 offer + up to 10
     * collection + up to 6 category) — this must stay >= that total or
     * banners start repeating images via the pool's modulo wraparound.
     */
    protected const BANNER_COUNT = 26;

    public function handle(DemoImageImporter $importer): int
    {
        if (! $importer->hasApiKey()) {
            $this->error('PEXELS_API_KEY is not set in .env — get a free key at https://www.pexels.com/api/ and set it before running this command.');

            return self::FAILURE;
        }

        $this->ensureStorageLink();

        $manifest = DemoImageManifest::load();
        $force = (bool) $this->option('force');

        $this->importCategoryAndProductPhotos($importer, $manifest, $force);
        $this->importPooledBucket($importer, $manifest, $force, 'blog', 'modest fashion elegant boutique lifestyle', self::BLOG_COUNT, 'blog/demo', 'demo-blog');
        $this->importPooledBucket($importer, $manifest, $force, 'brand_banners', 'fashion boutique elegant storefront', self::BRAND_COUNT, 'brands/demo', 'demo-brand-banner');
        $this->importPooledBucket($importer, $manifest, $force, 'banners', 'luxury fashion editorial elegant', self::BANNER_COUNT, 'banners/demo', 'demo-banner');
        $this->importCollectionPhotos($importer, $manifest, $force);

        $this->info('Image import complete.');

        if ($this->option('images-only')) {
            return self::SUCCESS;
        }

        $this->info('Running demo seeders...');
        Artisan::call('db:seed', ['--class' => 'DemoCatalogSeeder', '--force' => true], $this->getOutput());

        $this->info('Demo import complete — the store is now populated with demo data.');

        return self::SUCCESS;
    }

    protected function ensureStorageLink(): void
    {
        if (! is_link(public_path('storage'))) {
            $this->info('Creating storage symlink...');
            Artisan::call('storage:link', [], $this->getOutput());
        }
    }

    protected function importCategoryAndProductPhotos(DemoImageImporter $importer, array &$manifest, bool $force): void
    {
        foreach (DemoCategorySeeder::definitions() as $definition) {
            $slug = $definition['slug'];
            $poolNeeded = $definition['product_count'] * self::PRODUCTS_PER_ITEM;

            if (! $force && $this->categoryReady($manifest, $slug, $poolNeeded)) {
                $this->line("Skipping [{$slug}] — already imported.");

                continue;
            }

            $totalNeeded = $poolNeeded + 1;
            $this->info("Importing {$totalNeeded} photos for [{$slug}]...");

            $paths = $this->fetchInBatches($importer, $definition['search_query'], $totalNeeded, 'products/demo', "demo-{$slug}");

            if (count($paths) < 2) {
                $this->warn('Only '.count($paths)." photo(s) downloaded for [{$slug}] — products in this category will be skipped by the seeder until this succeeds.");

                continue;
            }

            $manifest['categories'][$slug] = array_shift($paths);
            $manifest['products'][$slug] = $paths;
            DemoImageManifest::save($manifest);
        }
    }

    protected function importCollectionPhotos(DemoImageImporter $importer, array &$manifest, bool $force): void
    {
        $collections = [
            'summer' => 'summer fashion light elegant',
            'winter' => 'winter fashion coat elegant',
            'ramadan' => 'ramadan lantern elegant arabic',
            'eid' => 'eid celebration elegant fashion',
            'luxury' => 'luxury fashion boutique gold',
            'classic' => 'classic elegant fashion timeless',
            'modern' => 'modern minimalist fashion elegant',
            'daily-wear' => 'everyday casual fashion elegant',
            'formal' => 'formal elegant fashion evening',
            'featured' => 'elegant fashion editorial boutique',
        ];

        foreach ($collections as $slug => $query) {
            $existing = $manifest['collections'][$slug] ?? null;

            if (! $force && $existing && Storage::disk('public')->exists($existing)) {
                $this->line("Skipping collection [{$slug}] — already imported.");

                continue;
            }

            $paths = $importer->fetchAndStore($query, 1, 'collections/demo', "demo-collection-{$slug}");

            if (empty($paths)) {
                $this->warn("No photo downloaded for collection [{$slug}].");

                continue;
            }

            $manifest['collections'][$slug] = $paths[0];
            DemoImageManifest::save($manifest);
        }
    }

    protected function importPooledBucket(DemoImageImporter $importer, array &$manifest, bool $force, string $key, string $query, int $count, string $directory, string $prefix): void
    {
        $ready = ! $force
            && count($manifest[$key] ?? []) >= $count
            && collect($manifest[$key])->every(fn ($p) => Storage::disk('public')->exists($p));

        if ($ready) {
            $this->line("Skipping [{$key}] — already imported.");

            return;
        }

        $this->info("Importing {$count} photos for [{$key}]...");
        $paths = $this->fetchInBatches($importer, $query, $count, $directory, $prefix);

        if (empty($paths)) {
            $this->warn("No photos could be downloaded for [{$key}].");

            return;
        }

        $manifest[$key] = $paths;
        DemoImageManifest::save($manifest);
    }

    /**
     * Pexels caps per_page at 80, so anything larger needs multiple calls.
     *
     * @return array<int, string>
     */
    protected function fetchInBatches(DemoImageImporter $importer, string $query, int $total, string $directory, string $prefix): array
    {
        $paths = [];
        $remaining = $total;
        $batch = 0;

        while ($remaining > 0) {
            $take = min($remaining, 80);
            $paths = array_merge($paths, $importer->fetchAndStore($query, $take, $directory, "{$prefix}-b{$batch}"));
            $remaining -= $take;
            $batch++;
        }

        return $paths;
    }

    protected function categoryReady(array $manifest, string $slug, int $poolNeeded): bool
    {
        $cover = $manifest['categories'][$slug] ?? null;
        $pool = $manifest['products'][$slug] ?? [];

        if (! $cover || ! Storage::disk('public')->exists($cover)) {
            return false;
        }

        if (count($pool) < $poolNeeded) {
            return false;
        }

        return collect($pool)->every(fn ($p) => Storage::disk('public')->exists($p));
    }
}
