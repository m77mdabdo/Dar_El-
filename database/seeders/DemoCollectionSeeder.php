<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Services\DemoImageManifest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoCollectionSeeder extends Seeder
{
    public function run(): void
    {
        $manifest = DemoImageManifest::load();

        foreach ($this->definitions() as $sortOrder => $definition) {
            $image = $manifest['collections'][$definition['slug']] ?? null;

            if (! $image || ! Storage::disk('public')->exists($image)) {
                $this->command?->warn("Skipping collection [{$definition['slug']}] — no imported image available yet. Run php artisan demo:import first.");

                continue;
            }

            Collection::updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name_ar' => $definition['name_ar'],
                    'name_en' => $definition['name_en'],
                    'description_ar' => $definition['description_ar'],
                    'description_en' => $definition['description_en'],
                    'image' => $image,
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]
            );
        }
    }

    /**
     * @return array<int, array{slug: string, name_ar: string, name_en: string, description_ar: string, description_en: string}>
     */
    public static function definitions(): array
    {
        return [
            ['slug' => 'summer', 'name_ar' => 'مجموعة الصيف', 'name_en' => 'Summer', 'description_ar' => 'قطع خفيفة بألوان مشرقة تناسب أجواء الصيف الحارة.', 'description_en' => 'Lightweight pieces in bright tones, made for the warmth of summer.'],
            ['slug' => 'winter', 'name_ar' => 'مجموعة الشتاء', 'name_en' => 'Winter', 'description_ar' => 'طبقات دافئة وأقمشة كثيفة لمواجهة برد الشتاء بأناقة.', 'description_en' => 'Warm layers and richer fabrics to meet the cold in style.'],
            ['slug' => 'ramadan', 'name_ar' => 'مجموعة رمضان', 'name_en' => 'Ramadan', 'description_ar' => 'قطع مريحة وفاخرة لأمسيات رمضان وسهرات الإفطار.', 'description_en' => 'Comfortable, elevated pieces for Ramadan evenings and iftar gatherings.'],
            ['slug' => 'eid', 'name_ar' => 'مجموعة العيد', 'name_en' => 'Eid', 'description_ar' => 'إطلالات احتفالية فاخرة لأجمل أيام العيد.', 'description_en' => 'Celebratory, statement-making looks for the joy of Eid.'],
            ['slug' => 'luxury', 'name_ar' => 'مجموعة فاخرة', 'name_en' => 'Luxury', 'description_ar' => 'قطع مختارة من أرقى الأقمشة والتفاصيل الحرفية الدقيقة.', 'description_en' => 'Handpicked pieces in the finest fabrics and most exacting craftsmanship.'],
            ['slug' => 'classic', 'name_ar' => 'مجموعة كلاسيكية', 'name_en' => 'Classic', 'description_ar' => 'تصاميم خالدة لا تتأثر بتغيّر الموضة.', 'description_en' => 'Timeless designs that stay elegant well beyond any single trend.'],
            ['slug' => 'modern', 'name_ar' => 'مجموعة عصرية', 'name_en' => 'Modern', 'description_ar' => 'قصّات جريئة وخطوط بسيطة لإطلالة عصرية مميزة.', 'description_en' => 'Bold cuts and clean lines for a distinctly modern silhouette.'],
            ['slug' => 'daily-wear', 'name_ar' => 'إطلالات يومية', 'name_en' => 'Daily Wear', 'description_ar' => 'قطع عملية ومريحة لارتداء يومي دون مجهود إضافي.', 'description_en' => 'Practical, comfortable pieces for effortless everyday wear.'],
            ['slug' => 'formal', 'name_ar' => 'إطلالات رسمية', 'name_en' => 'Formal', 'description_ar' => 'قطع أنيقة ومحتشمة تناسب المناسبات الرسمية وأجواء العمل الراقية.', 'description_en' => 'Polished, modest pieces suited to formal occasions and elevated workwear.'],
            ['slug' => 'featured', 'name_ar' => 'مختارات مميزة', 'name_en' => 'Featured', 'description_ar' => 'أبرز القطع المختارة من فريق دار الجميلة لهذا الموسم.', 'description_en' => 'This season\'s standout pieces, handpicked by the Dar El Jamila team.'],
        ];
    }
}
