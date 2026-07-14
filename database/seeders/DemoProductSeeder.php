<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Collection as CollectionModel;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\DemoImageManifest;
use App\Services\VariantSkuGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DemoProductSeeder extends Seeder
{
    protected const IMAGES_PER_PRODUCT = 6; // 1 cover + 5 gallery

    protected const COLOR_PALETTE = [
        ['en' => 'Black', 'ar' => 'أسود', 'hex' => '#1a1a1a'],
        ['en' => 'White', 'ar' => 'أبيض', 'hex' => '#f7f5f2'],
        ['en' => 'Beige', 'ar' => 'بيج', 'hex' => '#d8c3a5'],
        ['en' => 'Brown', 'ar' => 'بني', 'hex' => '#6f4e37'],
        ['en' => 'Olive', 'ar' => 'زيتي', 'hex' => '#6b6f3a'],
        ['en' => 'Navy', 'ar' => 'كحلي', 'hex' => '#1b2a4a'],
        ['en' => 'Grey', 'ar' => 'رمادي', 'hex' => '#8c8c8c'],
        ['en' => 'Maroon', 'ar' => 'عنابي', 'hex' => '#7a1c2e'],
    ];

    protected const CLOTHING_SIZES = ['S', 'M', 'L', 'XL', 'XXL'];

    /** Category slugs where a garment size dimension makes sense. Everything else is Free Size. */
    protected const CLOTHING_CATEGORIES = [
        'abayas', 'kaftans', 'jalabiyas', 'isdal', 'evening-dresses', 'casual-dresses',
        'prayer-sets', 'modest-sets', 'jackets', 'coats',
    ];

    /** Category slug => [min, max] price in EGP. */
    protected const PRICE_RANGES = [
        'abayas' => [900, 2600], 'kaftans' => [1200, 3200], 'jalabiyas' => [500, 1400],
        'isdal' => [700, 1800], 'evening-dresses' => [1500, 4500], 'casual-dresses' => [450, 1300],
        'prayer-sets' => [350, 900], 'modest-sets' => [600, 1700], 'scarves' => [120, 450],
        'hijabs' => [90, 320], 'shawls' => [250, 700], 'jackets' => [700, 2000],
        'coats' => [1400, 3800], 'bags' => [450, 2800], 'belts' => [180, 650],
        'shoes' => [500, 2200], 'accessories' => [120, 750], 'jewelry' => [200, 1800],
    ];

    protected VariantSkuGenerator $skuGenerator;

    public function run(): void
    {
        $this->skuGenerator = app(VariantSkuGenerator::class);

        $manifest = DemoImageManifest::load();
        $brandIds = Brand::pluck('id')->all();
        $collectionIds = CollectionModel::pluck('id', 'slug');

        if (empty($brandIds)) {
            $this->command?->warn('Skipping products — no brands found. Run DemoBrandSeeder first.');

            return;
        }

        // sku is unique across ALL products (see ProductVariantController) —
        // seed from the DB, not an empty collection, so a second run that
        // adds new products can't generate a SKU colliding with a variant
        // seeded on a previous run.
        $existingSkus = collect(ProductVariant::whereNotNull('sku')->pluck('sku')->all());

        foreach (DemoCategorySeeder::definitions() as $categoryDef) {
            $slug = $categoryDef['slug'];
            $category = \App\Models\Category::where('slug', $slug)->first();
            $pool = $manifest['products'][$slug] ?? [];

            if (! $category || empty($pool)) {
                $this->command?->warn("Skipping products for [{$slug}] — category or images not ready.");

                continue;
            }

            $names = $this->generateNames($slug, $categoryDef['product_count']);
            $vocab = $this->vocabularyFor($slug);
            $priceRange = self::PRICE_RANGES[$slug] ?? [300, 1200];
            $isClothing = in_array($slug, self::CLOTHING_CATEGORIES, true);

            foreach ($names as $i => [$nameEn, $nameAr, $adjEn, $adjAr, $styleEn, $styleAr]) {
                $imageSlice = array_splice($pool, 0, self::IMAGES_PER_PRODUCT);

                if (empty($imageSlice)) {
                    break; // ran out of downloaded photos for this category
                }

                $slugified = Str::slug($nameEn).'-'.($i + 1);
                $skuBase = Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $slug), 0, 3)).'-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);

                $fabric = $vocab['fabrics'][$i % count($vocab['fabrics'])];
                $occasion = $vocab['occasions'][$i % count($vocab['occasions'])];

                $descriptionEn = "{$nameEn} — {$styleEn} in {$fabric['en']}, cut for {$occasion['en']}. A refined piece from the Dar El Jamila collection, designed with lasting quality in mind.";
                $descriptionAr = "{$nameAr} بخامة {$fabric['ar']}، مصمم لِـ{$occasion['ar']}. قطعة راقية من مجموعة دار الجميلة، صُنعت لتدوم.";

                $price = random_int((int) ($priceRange[0] / 10), (int) ($priceRange[1] / 10)) * 10;
                $onSale = random_int(1, 100) <= 30;
                $compareAtPrice = $onSale ? (int) round($price * (random_int(115, 145) / 100) / 10) * 10 : null;

                $badgeRoll = random_int(1, 100);
                $badge = match (true) {
                    $badgeRoll <= 15 => 'new',
                    $badgeRoll <= 30 => 'bestseller',
                    default => null,
                };

                $product = Product::updateOrCreate(
                    ['slug' => $slugified],
                    [
                        'category_id' => $category->id,
                        'brand_id' => $brandIds[array_rand($brandIds)],
                        'name_ar' => $nameAr,
                        'name_en' => $nameEn,
                        'description_ar' => $descriptionAr,
                        'description_en' => $descriptionEn,
                        'price' => $price,
                        'compare_at_price' => $compareAtPrice,
                        'sku' => $skuBase,
                        'barcode' => $this->fakeBarcode($skuBase),
                        'image_url' => $imageSlice[0],
                        'badge' => $badge,
                        'is_active' => true,
                        'is_featured' => random_int(1, 100) <= 12,
                        'status' => Product::STATUS_PUBLISHED,
                        'published_at' => now(),
                        'meta_title_ar' => $nameAr.' — دار الجميلة',
                        'meta_title_en' => $nameEn.' — Dar El Jamila',
                        'meta_description_ar' => $descriptionAr,
                        'meta_description_en' => $descriptionEn,
                        'sku_prefix' => $skuBase,
                        'default_low_stock_threshold' => Product::LOW_STOCK_THRESHOLD,
                        'weight' => $isClothing ? (random_int(3, 12) / 10) : (random_int(1, 20) / 10),
                        'dimensions' => $isClothing ? null : sprintf('%d x %d x %d cm', random_int(15, 45), random_int(10, 35), random_int(3, 15)),
                    ]
                );

                $this->attachGallery($product, $imageSlice);
                $this->attachSizes($product, $isClothing);
                $existingSkus = $this->attachColorVariants($product, $isClothing, $existingSkus);
                $this->attachCollections($product, $collectionIds);
            }
        }
    }

    protected function attachGallery(Product $product, array $imagePaths): void
    {
        if ($product->images()->exists()) {
            return; // already seeded on a previous run — idempotent no-op
        }

        foreach ($imagePaths as $sortOrder => $path) {
            $product->images()->create(['path' => $path, 'sort_order' => $sortOrder]);
        }
    }

    protected function attachSizes(Product $product, bool $isClothing): void
    {
        $sizes = $isClothing ? self::CLOTHING_SIZES : ['Free Size'];

        foreach ($sizes as $size) {
            $stockRoll = random_int(1, 100);
            $stock = match (true) {
                $stockRoll <= 10 => 0,                    // out of stock
                $stockRoll <= 25 => random_int(1, 5),      // low stock
                default => random_int(8, 60),              // healthy stock
            };

            $product->sizes()->firstOrCreate(['size' => $size], ['stock' => $stock]);
        }
    }

    /**
     * @param  Collection<int, string>  $existingSkus
     * @return Collection<int, string>
     */
    protected function attachColorVariants(Product $product, bool $isClothing, Collection $existingSkus): Collection
    {
        if ($product->options()->exists()) {
            return $existingSkus; // already seeded — idempotent no-op
        }

        $colorOption = $product->options()->create(['name_ar' => 'اللون', 'name_en' => 'Color', 'sort_order' => 0]);
        $colors = collect(self::COLOR_PALETTE)->shuffle()->take(random_int(2, 3))->values();

        $colorValues = $colors->map(fn ($color, $i) => $colorOption->values()->create([
            'name_ar' => $color['ar'], 'name_en' => $color['en'], 'sort_order' => $i,
            'is_active' => true, 'hex_color' => $color['hex'],
        ]));

        $sizeValues = collect([null]);

        if ($isClothing) {
            $sizeOption = $product->options()->create(['name_ar' => 'المقاس', 'name_en' => 'Size', 'sort_order' => 1]);
            $sizeValues = collect(self::CLOTHING_SIZES)->map(fn ($size, $i) => $sizeOption->values()->create([
                'name_ar' => $size, 'name_en' => $size, 'sort_order' => $i, 'is_active' => true,
            ]));
        }

        foreach ($colorValues as $colorValue) {
            foreach ($sizeValues as $sizeValue) {
                $values = $sizeValue ? collect([$colorValue, $sizeValue]) : collect([$colorValue]);

                $sku = $this->skuGenerator->build($product, $values, $existingSkus->all());
                $existingSkus->push($sku);

                $stockRoll = random_int(1, 100);
                $stock = match (true) {
                    $stockRoll <= 10 => 0,
                    $stockRoll <= 25 => random_int(1, 5),
                    default => random_int(5, 40),
                };

                $variant = $product->variants()->create([
                    'sku' => $sku,
                    'stock' => $stock,
                    'low_stock_threshold' => Product::LOW_STOCK_THRESHOLD,
                    'is_active' => true,
                ]);

                $variant->values()->attach($values->pluck('id'));
            }
        }

        return $existingSkus;
    }

    protected function attachCollections(Product $product, Collection $collectionIds): void
    {
        if ($product->collections()->exists() || $collectionIds->isEmpty()) {
            return;
        }

        $product->collections()->attach(
            $collectionIds->values()->shuffle()->take(random_int(1, 3))->all()
        );
    }

    protected function fakeBarcode(string $seed): string
    {
        $hash = crc32($seed);

        return '2'.str_pad((string) ($hash % 100000000000), 11, '0', STR_PAD_LEFT);
    }

    /**
     * Generates $count grammatically-correct, non-repeating "{Adjective}
     * {Style} {Noun}"-style bilingual product name pairs for a category by
     * cycling through that category's (pre-gender-agreed) adjective × style
     * word bank — real language composed from real vocabulary, never
     * "Product 1"-style placeholders.
     *
     * @return array<int, array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string}>
     */
    protected function generateNames(string $categorySlug, int $count): array
    {
        $vocab = $this->vocabularyFor($categorySlug);
        $adjectives = $vocab['adjectives'];
        $styles = $vocab['styles'];

        $names = [];
        $used = [];
        $attempt = 0;

        while (count($names) < $count && $attempt < $count * 10) {
            $adj = $adjectives[$attempt % count($adjectives)];
            $style = $styles[intdiv($attempt, count($adjectives)) % count($styles)];
            $key = $adj['en'].'|'.$style['en'];
            $attempt++;

            if (isset($used[$key])) {
                continue;
            }
            $used[$key] = true;

            $nameEn = "{$adj['en']} {$style['en']} {$vocab['noun_en']}";
            $nameAr = "{$vocab['noun_ar']} {$adj['ar']} {$style['ar']}";

            $names[] = [$nameEn, $nameAr, $adj['en'], $adj['ar'], $style['en'], $style['ar']];
        }

        return $names;
    }

    /**
     * @return array{noun_en: string, noun_ar: string, gender: string, adjectives: array, styles: array, fabrics: array, occasions: array}
     */
    protected function vocabularyFor(string $categorySlug): array
    {
        $feminine = in_array($categorySlug, ['abayas', 'jalabiyas', 'bags', 'jewelry'], true);
        $adjectives = $feminine ? $this->feminineAdjectives() : $this->masculineAdjectives();
        $styles = $feminine ? $this->feminineStyles() : $this->masculineStyles();

        $nouns = [
            'abayas' => ['عباية', 'Abaya'], 'kaftans' => ['قفطان', 'Kaftan'], 'jalabiyas' => ['جلابية', 'Jalabiya'],
            'isdal' => ['طقم إسدال', 'Isdal Set'], 'evening-dresses' => ['فستان سهرة', 'Evening Dress'],
            'casual-dresses' => ['فستان كاجوال', 'Casual Dress'], 'prayer-sets' => ['طقم صلاة', 'Prayer Set'],
            'modest-sets' => ['طقم محتشم', 'Modest Set'], 'scarves' => ['إيشارب', 'Scarf'],
            'hijabs' => ['حجاب', 'Hijab'], 'shawls' => ['شال', 'Shawl'], 'jackets' => ['جاكيت', 'Jacket'],
            'coats' => ['معطف', 'Coat'], 'bags' => ['حقيبة', 'Bag'], 'belts' => ['حزام', 'Belt'],
            'shoes' => ['حذاء', 'Shoe'], 'accessories' => ['إكسسوار', 'Accessory'], 'jewelry' => ['قطعة مجوهرات', 'Jewelry Piece'],
        ];

        $fabrics = [
            'abayas' => [['en' => 'premium crepe', 'ar' => 'الكريب الفاخر'], ['en' => 'flowing chiffon', 'ar' => 'الشيفون الانسيابي'], ['en' => 'matte silk', 'ar' => 'الحرير المطفي']],
            'kaftans' => [['en' => 'hand-embroidered silk', 'ar' => 'الحرير المطرز يدويًا'], ['en' => 'brocade', 'ar' => 'البروكار'], ['en' => 'velvet-trimmed satin', 'ar' => 'الساتان المطرّز بالمخمل']],
            'jalabiyas' => [['en' => 'breathable cotton', 'ar' => 'القطن القابل للتنفس'], ['en' => 'soft linen-blend', 'ar' => 'خليط الكتان الناعم'], ['en' => 'light jersey', 'ar' => 'الجيرسيه الخفيف']],
            'isdal' => [['en' => 'flowing georgette', 'ar' => 'الجورجيت الانسيابي'], ['en' => 'soft crepe', 'ar' => 'الكريب الناعم']],
            'evening-dresses' => [['en' => 'embellished tulle', 'ar' => 'التول المطعّم'], ['en' => 'satin', 'ar' => 'الساتان'], ['en' => 'velvet', 'ar' => 'المخمل']],
            'casual-dresses' => [['en' => 'soft cotton-blend', 'ar' => 'خليط القطن الناعم'], ['en' => 'light jersey', 'ar' => 'الجيرسيه الخفيف']],
            'prayer-sets' => [['en' => 'breathable cotton', 'ar' => 'القطن القابل للتنفس'], ['en' => 'soft crepe', 'ar' => 'الكريب الناعم']],
            'modest-sets' => [['en' => 'linen-blend', 'ar' => 'خليط الكتان'], ['en' => 'soft crepe', 'ar' => 'الكريب الناعم']],
            'scarves' => [['en' => 'pure silk', 'ar' => 'الحرير الخالص'], ['en' => 'soft chiffon', 'ar' => 'الشيفون الناعم']],
            'hijabs' => [['en' => 'breathable jersey', 'ar' => 'الجيرسيه القابل للتنفس'], ['en' => 'soft chiffon', 'ar' => 'الشيفون الناعم']],
            'shawls' => [['en' => 'brushed wool-blend', 'ar' => 'خليط الصوف المصقول'], ['en' => 'soft pashmina', 'ar' => 'الباشمينا الناعمة']],
            'jackets' => [['en' => 'structured wool-blend', 'ar' => 'خليط الصوف المهيكل'], ['en' => 'soft suede', 'ar' => 'السويدي الناعم']],
            'coats' => [['en' => 'heavy wool-blend', 'ar' => 'خليط الصوف الثقيل'], ['en' => 'brushed cashmere-blend', 'ar' => 'خليط الكشمير المصقول']],
            'bags' => [['en' => 'genuine leather', 'ar' => 'الجلد الطبيعي'], ['en' => 'suede', 'ar' => 'السويدي']],
            'belts' => [['en' => 'genuine leather', 'ar' => 'الجلد الطبيعي']],
            'shoes' => [['en' => 'genuine leather', 'ar' => 'الجلد الطبيعي'], ['en' => 'suede', 'ar' => 'السويدي']],
            'accessories' => [['en' => 'polished metal', 'ar' => 'المعدن المصقول'], ['en' => 'genuine leather', 'ar' => 'الجلد الطبيعي']],
            'jewelry' => [['en' => 'gold-plated brass', 'ar' => 'النحاس المطلي بالذهب'], ['en' => 'polished metal', 'ar' => 'المعدن المصقول']],
        ];

        $occasions = [
            'abayas' => [['en' => 'everyday elegance', 'ar' => 'الأناقة اليومية'], ['en' => 'special occasions', 'ar' => 'المناسبات الخاصة']],
            'kaftans' => [['en' => 'festive gatherings', 'ar' => 'المناسبات الاحتفالية'], ['en' => 'grand celebrations', 'ar' => 'الاحتفالات الكبرى']],
            'jalabiyas' => [['en' => 'relaxed days at home', 'ar' => 'أيام الراحة في المنزل'], ['en' => 'easy everyday wear', 'ar' => 'الارتداء اليومي السهل']],
            'isdal' => [['en' => 'modest everyday coverage', 'ar' => 'الحشمة اليومية'], ['en' => 'graceful daily wear', 'ar' => 'الإطلالة اليومية الأنيقة']],
            'evening-dresses' => [['en' => 'weddings and galas', 'ar' => 'حفلات الزفاف والسهرات'], ['en' => 'unforgettable evenings', 'ar' => 'الليالي التي لا تُنسى']],
            'casual-dresses' => [['en' => 'everyday errands', 'ar' => 'المشاوير اليومية'], ['en' => 'relaxed weekends', 'ar' => 'عطلات نهاية الأسبوع الهادئة']],
            'prayer-sets' => [['en' => 'peaceful worship', 'ar' => 'العبادة الهادئة'], ['en' => 'daily prayer', 'ar' => 'الصلاة اليومية']],
            'modest-sets' => [['en' => 'effortless coordinated style', 'ar' => 'الأناقة المنسّقة دون عناء'], ['en' => 'everyday polish', 'ar' => 'الأناقة اليومية']],
            'scarves' => [['en' => 'finishing any look', 'ar' => 'تكملة أي إطلالة'], ['en' => 'everyday styling', 'ar' => 'التنسيق اليومي']],
            'hijabs' => [['en' => 'all-day comfort', 'ar' => 'الراحة طوال اليوم'], ['en' => 'effortless daily styling', 'ar' => 'التنسيق اليومي السهل']],
            'shawls' => [['en' => 'cooler evenings', 'ar' => 'الأمسيات الباردة'], ['en' => 'an extra layer of elegance', 'ar' => 'طبقة إضافية من الأناقة']],
            'jackets' => [['en' => 'layering over any outfit', 'ar' => 'التنسيق فوق أي إطلالة'], ['en' => 'transitional weather', 'ar' => 'أجواء الفصول المتغيرة']],
            'coats' => [['en' => 'the coldest days', 'ar' => 'أشد أيام البرد'], ['en' => 'winter elegance', 'ar' => 'أناقة الشتاء']],
            'bags' => [['en' => 'everyday carry', 'ar' => 'الاستخدام اليومي'], ['en' => 'evening occasions', 'ar' => 'المناسبات المسائية']],
            'belts' => [['en' => 'finishing any silhouette', 'ar' => 'تكملة أي إطلالة'], ['en' => 'a refined detail', 'ar' => 'لمسة أنيقة دقيقة']],
            'shoes' => [['en' => 'everyday comfort', 'ar' => 'الراحة اليومية'], ['en' => 'special occasions', 'ar' => 'المناسبات الخاصة']],
            'accessories' => [['en' => 'completing any outfit', 'ar' => 'تكملة أي إطلالة'], ['en' => 'everyday styling', 'ar' => 'التنسيق اليومي']],
            'jewelry' => [['en' => 'a special shine', 'ar' => 'بريق مميز'], ['en' => 'grand occasions', 'ar' => 'المناسبات الكبرى']],
        ];

        [$nounAr, $nounEn] = $nouns[$categorySlug] ?? ['منتج', 'Piece'];

        return [
            'noun_en' => $nounEn,
            'noun_ar' => $nounAr,
            'gender' => $feminine ? 'f' : 'm',
            'adjectives' => $adjectives,
            'styles' => $styles,
            'fabrics' => $fabrics[$categorySlug] ?? [['en' => 'premium fabric', 'ar' => 'خامة فاخرة']],
            'occasions' => $occasions[$categorySlug] ?? [['en' => 'everyday elegance', 'ar' => 'الأناقة اليومية']],
        ];
    }

    protected function masculineAdjectives(): array
    {
        return [
            ['en' => 'Emerald', 'ar' => 'زمردي'], ['en' => 'Midnight', 'ar' => 'ليلي'], ['en' => 'Ivory', 'ar' => 'عاجي'],
            ['en' => 'Rose Gold', 'ar' => 'وردي ذهبي'], ['en' => 'Champagne', 'ar' => 'شمبانيا'], ['en' => 'Onyx', 'ar' => 'أونيكس'],
            ['en' => 'Pearl', 'ar' => 'لؤلئي'], ['en' => 'Sapphire', 'ar' => 'ياقوتي'], ['en' => 'Amber', 'ar' => 'كهرماني'],
            ['en' => 'Dusty Rose', 'ar' => 'وردي ترابي'], ['en' => 'Mocha', 'ar' => 'موكا'], ['en' => 'Golden', 'ar' => 'ذهبي'],
            ['en' => 'Charcoal', 'ar' => 'فحمي'], ['en' => 'Ebony', 'ar' => 'أبنوسي'],
        ];
    }

    protected function feminineAdjectives(): array
    {
        return [
            ['en' => 'Emerald', 'ar' => 'زمردية'], ['en' => 'Midnight', 'ar' => 'ليلية'], ['en' => 'Ivory', 'ar' => 'عاجية'],
            ['en' => 'Rose Gold', 'ar' => 'وردية ذهبية'], ['en' => 'Champagne', 'ar' => 'شمبانيا'], ['en' => 'Onyx', 'ar' => 'أونيكس'],
            ['en' => 'Pearl', 'ar' => 'لؤلؤية'], ['en' => 'Sapphire', 'ar' => 'ياقوتية'], ['en' => 'Amber', 'ar' => 'كهرمانية'],
            ['en' => 'Dusty Rose', 'ar' => 'وردية ترابية'], ['en' => 'Mocha', 'ar' => 'موكا'], ['en' => 'Golden', 'ar' => 'ذهبية'],
            ['en' => 'Charcoal', 'ar' => 'فحمية'], ['en' => 'Ebony', 'ar' => 'أبنوسية'],
        ];
    }

    protected function masculineStyles(): array
    {
        return [
            ['en' => 'Embroidered', 'ar' => 'مطرز'], ['en' => 'Hand-Beaded', 'ar' => 'مطعّم بالخرز'],
            ['en' => 'Crystal-Embellished', 'ar' => 'مرصّع بالكريستال'], ['en' => 'Draped', 'ar' => 'مُدرّج'],
            ['en' => 'Tailored', 'ar' => 'مفصّل'], ['en' => 'Flowing', 'ar' => 'انسيابي'],
            ['en' => 'Minimalist', 'ar' => 'بسيط'], ['en' => 'Classic', 'ar' => 'كلاسيكي'],
            ['en' => 'Structured', 'ar' => 'مهيكل'], ['en' => 'Hand-Finished', 'ar' => 'بلمسة يدوية'],
        ];
    }

    protected function feminineStyles(): array
    {
        return [
            ['en' => 'Embroidered', 'ar' => 'مطرزة'], ['en' => 'Hand-Beaded', 'ar' => 'مطعّمة بالخرز'],
            ['en' => 'Crystal-Embellished', 'ar' => 'مرصّعة بالكريستال'], ['en' => 'Draped', 'ar' => 'مُدرّجة'],
            ['en' => 'Tailored', 'ar' => 'مفصّلة'], ['en' => 'Flowing', 'ar' => 'انسيابية'],
            ['en' => 'Minimalist', 'ar' => 'بسيطة'], ['en' => 'Classic', 'ar' => 'كلاسيكية'],
            ['en' => 'Structured', 'ar' => 'مهيكلة'], ['en' => 'Hand-Finished', 'ar' => 'بلمسة يدوية'],
        ];
    }
}
