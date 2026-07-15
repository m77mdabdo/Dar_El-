<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsAppContactTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(string $nameEn = 'Test Abaya'): Product
    {
        $category = Category::create([
            'name_ar' => $nameEn, 'name_en' => $nameEn, 'slug' => Str::slug($nameEn).'-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name_ar' => $nameEn, 'name_en' => $nameEn, 'slug' => Str::slug($nameEn).'-'.uniqid(),
            'price' => 1000, 'is_active' => true, 'is_featured' => false,
        ]);
    }

    public function test_floating_whatsapp_button_renders_with_correct_link_when_number_is_configured(): void
    {
        Setting::set('whatsapp_number', '201234567890');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('id="dj-whatsapp-float"', false);
        $response->assertSee('https://wa.me/201234567890?text='.rawurlencode('مرحبًا، عندي سؤال عن موقع دار الجميلة.'), false);
    }

    public function test_floating_whatsapp_button_does_not_render_when_number_is_unset(): void
    {
        Setting::set('whatsapp_number', '');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('id="dj-whatsapp-float"', false);
    }

    public function test_product_page_ask_about_this_product_button_renders_with_correct_link_when_number_is_configured(): void
    {
        Setting::set('whatsapp_number', '201234567890');
        $product = $this->makeProduct('Golden Abaya');

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertSee('dj-ask-whatsapp', false);

        $expectedText = rawurlencode('مهتمة بالمنتج: Golden Abaya - '.route('shop.show', $product));
        $response->assertSee('https://wa.me/201234567890?text='.$expectedText, false);
    }

    public function test_product_page_ask_about_this_product_button_does_not_render_when_number_is_unset(): void
    {
        Setting::set('whatsapp_number', '');
        $product = $this->makeProduct('Silver Abaya');

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertDontSee('dj-ask-whatsapp', false);
    }
}
