<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Services\DemoImageManifest;
use App\Services\DemoLogoGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 45 fictional fashion labels sold through the Dar El-Jamila marketplace.
 * Logos are generated locally (a stock photo API has no way to return an
 * actual logo for a made-up brand — see DemoLogoGenerator); banners are
 * real downloaded boutique/fashion photography. Idempotent via
 * updateOrCreate keyed by slug; the logo is only (re)generated if the
 * brand doesn't already have one on disk, so re-running this seeder is
 * a fast no-op after the first run.
 */
class DemoBrandSeeder extends Seeder
{
    public function run(): void
    {
        $manifest = DemoImageManifest::load();
        $banners = $manifest['brand_banners'] ?? [];

        if (empty($banners)) {
            $this->command?->warn('Skipping brands — no imported banner photos available yet. Run php artisan demo:import first.');

            return;
        }

        $logoGenerator = app(DemoLogoGenerator::class);

        foreach ($this->definitions() as $index => $definition) {
            $existing = Brand::where('slug', $definition['slug'])->first();
            $logo = $existing?->logo;

            if (! $logo || ! Storage::disk('public')->exists($logo)) {
                $logo = $logoGenerator->generate($definition['name_en'], 'brands/demo', 'demo-brand-'.$definition['slug']);
            }

            $banner = $banners[$index % count($banners)];

            Brand::updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name_ar' => $definition['name_ar'],
                    'name_en' => $definition['name_en'],
                    'description_ar' => $definition['description_ar'],
                    'description_en' => $definition['description_en'],
                    'logo' => $logo,
                    'banner' => $banner,
                    'website' => 'https://'.$definition['slug'].'.example.com',
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }

    /**
     * @return array<int, array{slug: string, name_ar: string, name_en: string, description_ar: string, description_en: string}>
     */
    public static function definitions(): array
    {
        $names = [
            ['Layali Couture', 'ليالي كوتور'],
            ['Warda Atelier', 'أتيليه وردة'],
            ['Nour El Sham', 'نور الشام'],
            ['Qasr Al Anaqa', 'قصر الأناقة'],
            ['Zahret Dubai', 'زهرة دبي'],
            ['Amira House', 'بيت أميرة'],
            ['Sultana Silk', 'سلطانة الحرير'],
            ['Rose El Nil', 'وردة النيل'],
            ['Bariq Fashion', 'بريق فاشن'],
            ['Lamsa Deluxe', 'لمسة ديلوكس'],
            ['Jawhara Studio', 'استوديو جوهرة'],
            ['Reem Atelier', 'أتيليه ريم'],
            ['Sahar Collection', 'مجموعة سحر'],
            ['Yasmine House', 'بيت ياسمين'],
            ['Dana Couture', 'دانة كوتور'],
            ['Malak Boutique', 'بوتيك ملاك'],
            ['Farah Studio', 'استوديو فرح'],
            ['Layan Atelier', 'أتيليه ليان'],
            ['Nawara Collection', 'مجموعة نوارة'],
            ['Rimal Silk House', 'بيت الحرير - رمال'],
            ['Aseel Couture', 'أصيل كوتور'],
            ['Bahja Boutique', 'بوتيك بهجة'],
            ['Elana Studio', 'استوديو إيلانا'],
            ['Roya Atelier', 'أتيليه رؤية'],
            ['Sundus Silk', 'سندس للحرير'],
            ['Talia House', 'بيت تاليا'],
            ['Nayla Couture', 'نايلة كوتور'],
            ['Hala Fashion House', 'بيت هالة للأزياء'],
            ['Mira Deluxe', 'ميرا ديلوكس'],
            ['Widad Atelier', 'أتيليه وداد'],
            ['Ghalia Boutique', 'بوتيك غالية'],
            ['Salma Studio', 'استوديو سلمى'],
            ['Rawan Collection', 'مجموعة روان'],
            ['Alia Couture', 'عالية كوتور'],
            ['Dima Silk House', 'بيت ديما للحرير'],
            ['Farida Atelier', 'أتيليه فريدة'],
            ['Joud Fashion', 'جود فاشن'],
            ['Kinda Boutique', 'بوتيك كندة'],
            ['Lina House', 'بيت لينا'],
            ['Maram Studio', 'استوديو مرام'],
            ['Nadia Couture', 'نادية كوتور'],
            ['Rania Atelier', 'أتيليه رانيا'],
            ['Sama Deluxe', 'سما ديلوكس'],
            ['Tuqa Collection', 'مجموعة تقى'],
            ['Yara Fashion House', 'بيت يارا للأزياء'],
        ];

        $descriptionTemplates = [
            ['en' => ':name designs modest fashion pieces defined by careful tailoring and a quiet sense of luxury.', 'ar' => 'تُصمّم :name قطعًا محتشمة تتميّز بدقة التفصيل وإحساس هادئ بالفخامة.'],
            ['en' => 'Since its founding, :name has built a name for elegant silhouettes crafted from premium fabrics.', 'ar' => 'منذ تأسيسها، بنت :name اسمها على قصّات أنيقة مصنوعة من أقمشة فاخرة.'],
            ['en' => ':name blends contemporary style with timeless modest-wear traditions.', 'ar' => 'تمزج :name بين الأسلوب المعاصر وتقاليد الأزياء المحتشمة الخالدة.'],
            ['en' => 'Known for its attention to detail, :name creates pieces meant to last well beyond a single season.', 'ar' => 'تشتهر :name بدقة تفاصيلها، وتُصمّم قطعًا تدوم أثرها لأكثر من موسم واحد.'],
            ['en' => ':name is a favorite for special-occasion pieces that balance tradition and modern elegance.', 'ar' => 'تُعد :name من العلامات المفضّلة للقطع المخصصة للمناسبات، بتوازن بين الأصالة والأناقة العصرية.'],
        ];

        return collect($names)->values()->map(function ($pair, $index) use ($descriptionTemplates) {
            [$nameEn, $nameAr] = $pair;
            $template = $descriptionTemplates[$index % count($descriptionTemplates)];

            return [
                'slug' => Str::slug($nameEn),
                'name_en' => $nameEn,
                'name_ar' => $nameAr,
                'description_en' => str_replace(':name', $nameEn, $template['en']),
                'description_ar' => str_replace(':name', $nameAr, $template['ar']),
            ];
        })->all();
    }
}
