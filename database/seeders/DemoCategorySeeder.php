<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Services\DemoImageManifest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * The 18 real garment categories behind the demo catalog. Keyed by slug and
 * written via updateOrCreate, so re-running this seeder only refreshes
 * copy/images rather than duplicating rows — and it never touches the
 * pre-existing "kaftans"/"hijabs"/etc. categories from the original
 * CategorySeeder beyond enriching the same slug.
 *
 * "New Arrivals" / "Best Sellers" / "Offers" were deliberately NOT created
 * as categories: a product belongs to exactly one category in this schema,
 * so forcing a product's category to a marketing label instead of its real
 * garment type would break normal category browsing. Those three concepts
 * are instead expressed via Product::badge ('new'/'bestseller') and
 * compare_at_price, which is what the storefront actually renders today.
 */
class DemoCategorySeeder extends Seeder
{
    public function run(): void
    {
        $manifest = DemoImageManifest::load();

        foreach ($this->definitions() as $sortOrder => $definition) {
            $image = $manifest['categories'][$definition['slug']] ?? null;

            if (! $image || ! Storage::disk('public')->exists($image)) {
                $this->command?->warn("Skipping category [{$definition['slug']}] — no imported image available yet. Run php artisan demo:import first.");

                continue;
            }

            Category::updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name_ar' => $definition['name_ar'],
                    'name_en' => $definition['name_en'],
                    'description_ar' => $definition['description_ar'],
                    'description_en' => $definition['description_en'],
                    'meta_title_ar' => $definition['name_ar'].' — دار الجميلة',
                    'meta_title_en' => $definition['name_en'].' — Dar El Jamila',
                    'meta_description_ar' => $definition['description_ar'],
                    'meta_description_en' => $definition['description_en'],
                    'image' => $image,
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]
            );
        }
    }

    /**
     * @return array<int, array{slug: string, name_ar: string, name_en: string, description_ar: string, description_en: string, product_count: int, search_query: string}>
     */
    public static function definitions(): array
    {
        return [
            [
                'slug' => 'abayas', 'name_ar' => 'عبايات', 'name_en' => 'Abayas',
                'description_ar' => 'عبايات فاخرة بقصّات عصرية وتفصيل دقيق، من القماش اليومي البسيط إلى القطع المطرزة المخصصة للمناسبات.',
                'description_en' => 'Luxurious abayas in modern silhouettes and precise tailoring — from everyday simplicity to richly embroidered occasion pieces.',
                'product_count' => 14, 'search_query' => 'elegant black abaya modest fashion',
            ],
            [
                'slug' => 'kaftans', 'name_ar' => 'قفاطين', 'name_en' => 'Kaftans',
                'description_ar' => 'قفاطين مطرزة بحرفية عالية، مستوحاة من التراث ومصممة لتناسب الحاضر بألوان وقصّات متجددة.',
                'description_en' => 'Richly embroidered kaftans rooted in heritage craftsmanship, reimagined with contemporary colors and flowing cuts.',
                'product_count' => 8, 'search_query' => 'embroidered kaftan modest fashion',
            ],
            [
                'slug' => 'jalabiyas', 'name_ar' => 'جلابيات', 'name_en' => 'Jalabiyas',
                'description_ar' => 'جلابيات مريحة وأنيقة للإطلالة اليومية، بأقمشة خفيفة تجمع بين الحشمة والراحة طوال اليوم.',
                'description_en' => 'Comfortable, elegant jalabiyas for everyday wear — lightweight fabrics that balance modesty and all-day comfort.',
                'product_count' => 8, 'search_query' => 'modest long dress fashion elegant',
            ],
            [
                'slug' => 'isdal', 'name_ar' => 'إسدال', 'name_en' => 'Isdal',
                'description_ar' => 'قطع إسدال طويلة بخطوط انسيابية، مثالية لإطلالة محتشمة وأنيقة في آن واحد.',
                'description_en' => 'Long, flowing isdal pieces with graceful lines — modest coverage that never sacrifices elegance.',
                'product_count' => 6, 'search_query' => 'long modest maxi dress fashion',
            ],
            [
                'slug' => 'evening-dresses', 'name_ar' => 'فساتين سهرة', 'name_en' => 'Evening Dresses',
                'description_ar' => 'فساتين سهرة محتشمة بتفاصيل فاخرة، صُممت لتُضيء لياليكِ الخاصة من حفلات الزفاف إلى المناسبات الكبرى.',
                'description_en' => 'Modest evening dresses with luxurious detailing, designed to shine at weddings, galas, and every special night.',
                'product_count' => 10, 'search_query' => 'elegant modest evening gown woman fashion',
            ],
            [
                'slug' => 'casual-dresses', 'name_ar' => 'فساتين كاجوال', 'name_en' => 'Casual Dresses',
                'description_ar' => 'فساتين كاجوال محتشمة تناسب الحياة اليومية، بأقمشة عملية وألوان هادئة تسهّل التنسيق.',
                'description_en' => 'Easy, modest casual dresses built for everyday life — practical fabrics and calm colors that mix and match effortlessly.',
                'product_count' => 10, 'search_query' => 'casual modest dress woman fashion',
            ],
            [
                'slug' => 'prayer-sets', 'name_ar' => 'أطقم صلاة', 'name_en' => 'Prayer Sets',
                'description_ar' => 'أطقم صلاة واسعة ومريحة من أقمشة ناعمة خفيفة، مصممة لسهولة الحركة أثناء العبادة.',
                'description_en' => 'Soft, breathable prayer sets cut for full ease of movement — designed with worship comfort as the first priority.',
                'product_count' => 6, 'search_query' => 'white prayer dress modest fashion',
            ],
            [
                'slug' => 'modest-sets', 'name_ar' => 'أطقم محتشمة', 'name_en' => 'Modest Sets',
                'description_ar' => 'أطقم من قطعتين متناسقتين، تمنحكِ إطلالة متكاملة وأنيقة دون عناء التنسيق.',
                'description_en' => 'Coordinated two-piece sets that deliver a complete, polished look without the effort of mixing separates.',
                'product_count' => 8, 'search_query' => 'modest two piece fashion set',
            ],
            [
                'slug' => 'scarves', 'name_ar' => 'إيشاربات', 'name_en' => 'Scarves',
                'description_ar' => 'إيشاربات حريرية فاخرة بألوان وأنماط متنوعة، إضافة أنيقة تكمل أي إطلالة.',
                'description_en' => 'Luxurious silk scarves in a range of colors and patterns — the finishing touch that elevates any outfit.',
                'product_count' => 10, 'search_query' => 'silk scarf fashion elegant',
            ],
            [
                'slug' => 'hijabs', 'name_ar' => 'حجابات', 'name_en' => 'Hijabs',
                'description_ar' => 'حجابات يومية وفخمة من أقمشة مختارة بعناية، سهلة التنسيق ومريحة طوال اليوم.',
                'description_en' => 'Everyday and premium hijabs in carefully chosen fabrics — easy to style, comfortable all day long.',
                'product_count' => 12, 'search_query' => 'hijab fashion woman elegant',
            ],
            [
                'slug' => 'shawls', 'name_ar' => 'شالات', 'name_en' => 'Shawls',
                'description_ar' => 'شالات دافئة وأنيقة تضيف طبقة من الفخامة لإطلالتكِ في الأجواء الباردة.',
                'description_en' => 'Warm, elegant shawls that add a refined layer to your look through the cooler months.',
                'product_count' => 8, 'search_query' => 'wool shawl fashion elegant woman',
            ],
            [
                'slug' => 'jackets', 'name_ar' => 'جاكيتات', 'name_en' => 'Jackets',
                'description_ar' => 'جاكيتات نسائية عصرية تُنسّق بسهولة فوق أي إطلالة لإضفاء لمسة أنيقة وعملية.',
                'description_en' => 'Modern women\'s jackets that layer effortlessly over any outfit for a practical, polished finish.',
                'product_count' => 8, 'search_query' => 'women fashion jacket elegant',
            ],
            [
                'slug' => 'coats', 'name_ar' => 'معاطف', 'name_en' => 'Coats',
                'description_ar' => 'معاطف شتوية فاخرة بقصّات طويلة أنيقة، توازن بين الدفء والرقي في المواسم الباردة.',
                'description_en' => 'Luxurious winter coats in elegant long silhouettes — warmth and refinement balanced for the coldest days.',
                'product_count' => 6, 'search_query' => 'women winter coat fashion elegant',
            ],
            [
                'slug' => 'bags', 'name_ar' => 'حقائب', 'name_en' => 'Bags',
                'description_ar' => 'حقائب فاخرة بتصاميم متعددة، من الحقائب اليومية العملية إلى قطع السهرة المميزة.',
                'description_en' => 'Luxury bags in a range of designs — from practical everyday carries to statement evening pieces.',
                'product_count' => 12, 'search_query' => 'luxury handbag fashion',
            ],
            [
                'slug' => 'belts', 'name_ar' => 'أحزمة', 'name_en' => 'Belts',
                'description_ar' => 'أحزمة جلدية أنيقة تضيف لمسة تفصيلية دقيقة لأي قطعة في خزانتكِ.',
                'description_en' => 'Elegant leather belts that add a refined, considered detail to any piece in your wardrobe.',
                'product_count' => 6, 'search_query' => 'leather belt fashion accessory elegant',
            ],
            [
                'slug' => 'shoes', 'name_ar' => 'أحذية', 'name_en' => 'Shoes',
                'description_ar' => 'أحذية نسائية أنيقة تجمع بين الراحة والفخامة، مناسبة لليوميات والمناسبات معًا.',
                'description_en' => 'Elegant women\'s shoes that balance comfort and luxury — suited to everyday wear and special occasions alike.',
                'product_count' => 10, 'search_query' => 'women fashion shoes elegant',
            ],
            [
                'slug' => 'accessories', 'name_ar' => 'إكسسوارات', 'name_en' => 'Accessories',
                'description_ar' => 'إكسسوارات مختارة بعناية لإكمال إطلالتكِ بلمسات أنيقة ومميزة.',
                'description_en' => 'Carefully curated accessories that complete your look with elegant, distinctive finishing touches.',
                'product_count' => 10, 'search_query' => 'fashion accessories elegant flatlay',
            ],
            [
                'slug' => 'jewelry', 'name_ar' => 'مجوهرات', 'name_en' => 'Jewelry',
                'description_ar' => 'قطع مجوهرات راقية تضيف بريقًا خاصًا لإطلالتكِ اليومية أو المناسبات الكبرى.',
                'description_en' => 'Refined jewelry pieces that add a special shine to everyday looks and grand occasions alike.',
                'product_count' => 8, 'search_query' => 'elegant jewelry fashion gold',
            ],
        ];
    }
}
