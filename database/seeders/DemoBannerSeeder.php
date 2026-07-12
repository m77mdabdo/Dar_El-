<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Collection as CollectionModel;
use App\Services\DemoImageManifest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoBannerSeeder extends Seeder
{
    public function run(): void
    {
        $manifest = DemoImageManifest::load();
        $pool = $manifest['banners'] ?? [];

        if (empty($pool)) {
            $this->command?->warn('Skipping banners — no imported images available yet. Run php artisan demo:import first.');

            return;
        }

        // Hero-type banners are deliberately never seeded here — the
        // homepage's hero is the store owner's own single static image
        // (home_hero_image Setting), and an earlier version of this
        // seeder briefly caused the homepage to silently swap to a
        // demo-banner slider whenever hero banners existed, overriding a
        // real store's homepage. Only the below-the-fold sections
        // (offers/collections/categories) are demo-seeded now.
        $all = array_merge(
            $this->offerDefinitions(),
            $this->collectionDefinitions(),
            $this->categoryDefinitions(),
        );

        foreach ($all as $sortOrder => $definition) {
            $image = $pool[$sortOrder % count($pool)];

            if (! Storage::disk('public')->exists($image)) {
                continue;
            }

            Banner::updateOrCreate(
                ['title_en' => $definition['title_en'], 'type' => $definition['type']],
                [
                    'title_ar' => $definition['title_ar'],
                    'subtitle_ar' => $definition['subtitle_ar'] ?? null,
                    'subtitle_en' => $definition['subtitle_en'] ?? null,
                    'image' => $image,
                    'link_url' => $definition['link_url'] ?? null,
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]
            );
        }
    }

    protected function offerDefinitions(): array
    {
        return [
            ['type' => Banner::TYPE_OFFER, 'title_en' => 'Up to 30% Off Evening Wear', 'title_ar' => 'خصم يصل إلى 30% على فساتين السهرة', 'subtitle_en' => 'For a limited time only.', 'subtitle_ar' => 'لفترة محدودة فقط.', 'link_url' => '/shop'],
            ['type' => Banner::TYPE_OFFER, 'title_en' => 'New Season, New Savings', 'title_ar' => 'موسم جديد وتوفير أكبر', 'subtitle_en' => 'Refresh your wardrobe for less.', 'subtitle_ar' => 'جدّدي خزانتكِ بأسعار مميزة.', 'link_url' => '/shop'],
            ['type' => Banner::TYPE_OFFER, 'title_en' => 'Bundle & Save on Sets', 'title_ar' => 'وفّري أكثر عند شراء الأطقم', 'subtitle_en' => 'Complete looks at a better price.', 'subtitle_ar' => 'إطلالات متكاملة بسعر أفضل.', 'link_url' => '/shop'],
        ];
    }

    protected function collectionDefinitions(): array
    {
        return CollectionModel::orderBy('sort_order')->get()->map(fn ($collection) => [
            'type' => Banner::TYPE_COLLECTION,
            'title_en' => $collection->name_en, 'title_ar' => $collection->name_ar,
            'subtitle_en' => $collection->description_en, 'subtitle_ar' => $collection->description_ar,
            'link_url' => '/shop?collection='.$collection->slug,
        ])->all();
    }

    protected function categoryDefinitions(): array
    {
        return Category::orderBy('sort_order')->limit(6)->get()->map(fn ($category) => [
            'type' => Banner::TYPE_CATEGORY,
            'title_en' => $category->name_en, 'title_ar' => $category->name_ar,
            'subtitle_en' => null, 'subtitle_ar' => null,
            'link_url' => '/shop?category='.$category->slug,
        ])->all();
    }
}
