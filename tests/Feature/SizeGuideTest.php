<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SizeGuideTest extends TestCase
{
    use RefreshDatabase;

    protected ?Category $defaultCategory = null;

    protected function defaultCategory(): Category
    {
        return $this->defaultCategory ??= Category::create([
            'name_ar' => 'General', 'name_en' => 'General', 'slug' => 'general-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);
    }

    protected function makeProduct(string $name = 'Product'): Product
    {
        $product = Product::create([
            'category_id' => $this->defaultCategory()->id,
            'name_ar' => $name, 'name_en' => $name, 'slug' => Str::slug($name).'-'.uniqid(),
            'price' => 1000, 'is_active' => true, 'is_featured' => false,
        ]);
        $product->sizes()->create(['size' => 'M', 'stock' => 5]);

        return $product;
    }

    protected function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    // ---------------------------------------------------------------
    // Setting::sizeGuideChart() / sizeGuideNote()
    // ---------------------------------------------------------------

    public function test_size_guide_chart_falls_back_to_default_measurements_when_unset(): void
    {
        $chart = Setting::sizeGuideChart();

        $this->assertCount(5, $chart);
        $this->assertSame(['S', 'M', 'L', 'XL', 'XXL'], array_column($chart, 'size'));
        // Every row has all 4 measurements present and numeric.
        foreach ($chart as $row) {
            foreach (['bust', 'waist', 'hips', 'length'] as $column) {
                $this->assertArrayHasKey($column, $row);
                $this->assertIsInt($row[$column]);
            }
        }
    }

    public function test_size_guide_chart_returns_admin_saved_values_once_set(): void
    {
        Setting::set('size_guide_chart', json_encode([
            ['size' => 'S', 'bust' => 90, 'waist' => 70, 'hips' => 95, 'length' => 135],
        ], JSON_UNESCAPED_UNICODE));

        $chart = Setting::sizeGuideChart();

        $this->assertCount(1, $chart);
        $this->assertSame(90, $chart[0]['bust']);
    }

    public function test_size_guide_note_falls_back_to_the_default_text_when_unset(): void
    {
        $this->assertSame('قد يختلف المقاس البسيط حسب تصميم القطعة', Setting::sizeGuideNote());
    }

    public function test_size_guide_note_returns_the_admin_saved_text_once_set(): void
    {
        Setting::set('size_guide_note', 'ملاحظة مخصصة للتجربة');

        $this->assertSame('ملاحظة مخصصة للتجربة', Setting::sizeGuideNote());
    }

    // ---------------------------------------------------------------
    // Storefront rendering
    // ---------------------------------------------------------------

    public function test_product_page_renders_the_size_guide_trigger_and_modal(): void
    {
        $product = $this->makeProduct('Size Guide Product');

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertSee('id="dj-size-guide-overlay"', false);
        $response->assertSee('djOpenSizeGuide()', false);
        $response->assertSee(__('Size Guide'));
    }

    public function test_product_page_shows_the_default_chart_values_in_the_modal(): void
    {
        $product = $this->makeProduct('Size Guide Defaults Product');

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        // Default M row: bust 96, waist 80, hips 104, length 142.
        $response->assertSeeInOrder(['96', '80', '104', '142']);
    }

    public function test_product_page_reflects_admin_saved_chart_values(): void
    {
        Setting::set('size_guide_chart', json_encode([
            ['size' => 'S', 'bust' => 999, 'waist' => 1, 'hips' => 2, 'length' => 3],
        ], JSON_UNESCAPED_UNICODE));

        $product = $this->makeProduct('Size Guide Custom Product');

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertSee('999');
    }

    public function test_product_page_shows_the_size_guide_note(): void
    {
        Setting::set('size_guide_note', 'ملاحظة اختبار فريدة تمامًا');

        $product = $this->makeProduct('Size Guide Note Product');

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertSee('ملاحظة اختبار فريدة تمامًا');
    }

    // ---------------------------------------------------------------
    // Admin settings form
    // ---------------------------------------------------------------

    public function test_admin_can_view_the_size_guide_form_with_current_values(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertSee(__('settings.size_guide'));
        $response->assertSee('name="size_guide[S][bust]"', false);
        $response->assertSee('name="size_guide[XXL][length]"', false);
        // Prefilled with the default chart's M-row bust measurement.
        $response->assertSee('value="96"', false);
    }

    public function test_admin_can_update_the_size_guide_chart_and_note(): void
    {
        $admin = $this->admin();

        $payload = [
            'size_guide_note' => 'ملاحظة محدثة من الأدمن',
            'size_guide' => [
                'S' => ['bust' => 91, 'waist' => 75, 'hips' => 99, 'length' => 139],
                'M' => ['bust' => 95, 'waist' => 79, 'hips' => 103, 'length' => 141],
                'L' => ['bust' => 99, 'waist' => 83, 'hips' => 107, 'length' => 143],
                'XL' => ['bust' => 103, 'waist' => 87, 'hips' => 111, 'length' => 145],
                'XXL' => ['bust' => 107, 'waist' => 91, 'hips' => 115, 'length' => 147],
            ],
        ];

        $response = $this->actingAs($admin)->patch(route('admin.settings.update'), $payload);

        $response->assertSessionHasNoErrors()->assertRedirect();

        $chart = Setting::sizeGuideChart();
        $this->assertSame(91, $chart[0]['bust']);
        $this->assertSame(147, $chart[4]['length']);
        $this->assertSame('ملاحظة محدثة من الأدمن', Setting::sizeGuideNote());
    }

    public function test_size_guide_measurement_cells_must_be_non_negative_integers(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'size_guide' => [
                'S' => ['bust' => -5, 'waist' => 75, 'hips' => 99, 'length' => 139],
            ],
        ]);

        $response->assertSessionHasErrors(['size_guide.S.bust']);
    }

    public function test_size_guide_fields_are_entirely_optional(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->patch(route('admin.settings.update'), []);

        $response->assertSessionHasNoErrors()->assertRedirect();
    }
}
