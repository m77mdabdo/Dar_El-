<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['name_en' => 'Emerald Silk Abaya', 'name_ar' => 'عباية حرير زمردية', 'category' => 'abayas', 'price' => 1450, 'badge' => 'new', 'image_url' => 'https://images.unsplash.com/photo-1772474500365-c2c520545f44?w=900&q=80&auto=format&fit=crop'],
            ['name_en' => 'Classic Black Abaya', 'name_ar' => 'عباية سوداء كلاسيكية', 'category' => 'abayas', 'price' => 950, 'badge' => null, 'image_url' => 'https://images.unsplash.com/photo-1772474587292-08b3e8932acd?w=900&q=80&auto=format&fit=crop'],
            ['name_en' => 'Embroidered Kaftan', 'name_ar' => 'قفطان مطرز', 'category' => 'kaftans', 'price' => 1750, 'badge' => 'bestseller', 'image_url' => 'https://images.unsplash.com/photo-1772474528936-4f1187eb1611?w=900&q=80&auto=format&fit=crop'],
            ['name_en' => 'Everyday Chiffon Hijab', 'name_ar' => 'حجاب شيفون يومي', 'category' => 'hijabs', 'price' => 180, 'badge' => null, 'image_url' => 'https://images.unsplash.com/photo-1772474557170-4818d01d7bca?w=900&q=80&auto=format&fit=crop'],
            ['name_en' => 'Pearl Hijab Pin Set', 'name_ar' => 'طقم دبابيس لؤلؤ', 'category' => 'accessories', 'price' => 120, 'badge' => 'new', 'image_url' => 'https://images.unsplash.com/photo-1772474569781-2fb1c6539f8c?w=900&q=80&auto=format&fit=crop'],
            ['name_en' => 'Royal Blue Kaftan', 'name_ar' => 'قفطان أزرق ملكي', 'category' => 'kaftans', 'price' => 1600, 'badge' => null, 'image_url' => 'https://images.unsplash.com/photo-1728487235101-664d87965931?w=900&q=80&auto=format&fit=crop'],
        ];

        foreach ($products as $data) {
            $category = Category::where('slug', $data['category'])->first();

            if (! $category) {
                continue;
            }

            $product = Product::firstOrCreate(
                ['slug' => Str::slug($data['name_en'])],
                [
                    'category_id' => $category->id,
                    'name_ar' => $data['name_ar'],
                    'name_en' => $data['name_en'],
                    'description_ar' => 'وصف المنتج بالعربية.',
                    'description_en' => 'A beautifully crafted piece from the Dar El Jamila collection.',
                    'price' => $data['price'],
                    'sku' => strtoupper(Str::random(8)),
                    'image_url' => $data['image_url'],
                    'badge' => $data['badge'],
                    'is_active' => true,
                    'is_featured' => $data['badge'] !== null,
                ]
            );

            foreach (['S', 'M', 'L', 'XL'] as $size) {
                $product->sizes()->firstOrCreate(['size' => $size], ['stock' => rand(3, 20)]);
            }
        }
    }
}
