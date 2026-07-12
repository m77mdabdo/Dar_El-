<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * The single entry point for the full demo catalog: `php artisan db:seed
 * --class=DemoCatalogSeeder` (or just `php artisan demo:import`, which
 * downloads the required photos first and then calls this). Every child
 * seeder is idempotent and additive — none of them touch existing
 * products/categories/blog posts/users beyond matching demo-specific slugs
 * or emails, so this is safe to run against a database that already has
 * real store data.
 *
 * Order matters: brands/collections/categories must exist before products
 * (which reference all three); products must exist before reviews and
 * before the banner seeder's collection/category banners.
 */
class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoBrandSeeder::class,
            DemoCollectionSeeder::class,
            DemoCategorySeeder::class,
            DemoProductSeeder::class,
            DemoBlogPostSeeder::class,
            DemoReviewSeeder::class,
            DemoOrderSeeder::class,
            DemoWishlistSeeder::class,
            DemoBannerSeeder::class,
        ]);
    }
}
