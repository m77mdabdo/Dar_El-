<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name_en' => 'Abayas', 'name_ar' => 'عبايات'],
            ['name_en' => 'Kaftans', 'name_ar' => 'قفاطين'],
            ['name_en' => 'Hijabs', 'name_ar' => 'حجابات'],
            ['name_en' => 'Accessories', 'name_ar' => 'إكسسوارات'],
        ];

        foreach ($categories as $index => $category) {
            Category::firstOrCreate(
                ['slug' => Str::slug($category['name_en'])],
                [
                    'name_ar' => $category['name_ar'],
                    'name_en' => $category['name_en'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }
}
