<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = [
            [
                'title_en' => 'How to Style Your Abaya for Any Occasion',
                'title_ar' => 'كيف تنسقين عبايتك لأي مناسبة',
            ],
            [
                'title_en' => 'The Art of Modest Fashion',
                'title_ar' => 'فن الموضة المحتشمة',
            ],
        ];

        foreach ($posts as $post) {
            BlogPost::firstOrCreate(
                ['slug' => Str::slug($post['title_en'])],
                [
                    'title_ar' => $post['title_ar'],
                    'title_en' => $post['title_en'],
                    'excerpt_ar' => 'مقتطف قصير من المقال بالعربية.',
                    'excerpt_en' => 'A short excerpt introducing the article.',
                    'body_ar' => 'محتوى المقال الكامل باللغة العربية.',
                    'body_en' => 'The full article content goes here.',
                    'is_published' => true,
                    'published_at' => now(),
                ]
            );
        }
    }
}
