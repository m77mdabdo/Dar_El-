<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewCustomerRegistered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_seeded_data(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        Role::findOrCreate('customer', 'web');

        $category = Category::create(['name_ar' => 'ع', 'name_en' => 'Cat', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $product = Product::create(['category_id' => $category->id, 'name_ar' => 'م', 'name_en' => 'Prod', 'slug' => 'prod-'.uniqid(), 'price' => 500, 'is_active' => true, 'is_featured' => false]);
        $product->sizes()->create(['size' => 'M', 'stock' => 3]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-'.uniqid(),
            'customer_name' => 'Test', 'customer_email' => 'test@example.com', 'customer_phone' => '010',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'x',
            'subtotal' => 500, 'shipping_fee' => 0, 'total' => 500, 'status' => 'delivered', 'payment_method' => 'cod',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name_en, 'size' => 'M', 'price' => 500, 'quantity' => 1]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee($order->order_number);
        $response->assertSee('Prod');
        $response->assertSee('dj-admin-chart', false);
    }

    public function test_dashboard_renders_with_no_data(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        Role::findOrCreate('customer', 'web');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    /**
     * Regression guard: the "Unread Notifications" stat card and the header
     * bell badge must always agree, since they're describing the exact same
     * thing. They used to disagree — the stat card's own $djCards array is
     * built in a @php block inside admin.dashboard.blade.php itself, which
     * runs before @extends('admin.layout') ever resolves the parent
     * template, so the admin.layout view composer (the only thing that ever
     * set $notifUnreadCount) hadn't fired yet and the card silently fell
     * back to its "?? 0" default — while the header, composed later as part
     * of the parent template, always showed the real number.
     */
    public function test_unread_notifications_stat_card_matches_the_header_bell_badge(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        Role::findOrCreate('customer', 'web');

        $admin->notify(new NewCustomerRegistered($admin));
        $admin->notify(new NewCustomerRegistered($admin));
        $admin->notify(new NewCustomerRegistered($admin));

        $this->assertSame(3, $admin->unreadNotifications()->count());

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();

        $html = $response->getContent();

        preg_match('/class="dj-admin-notif-badge[^"]*">\s*(\d+)/', $html, $badgeMatch);
        $this->assertNotEmpty($badgeMatch, 'Could not find the header notification badge in the dashboard response.');

        $label = preg_quote(__('admin.dashboard.unread_notifications'), '/');
        preg_match('/'.$label.'<\/p>\s*<p class="dj-admin-stat-value truncate">([\d,]+)<\/p>/', $html, $cardMatch);
        $this->assertNotEmpty($cardMatch, 'Could not find the unread-notifications stat card in the dashboard response.');

        $this->assertSame('3', $badgeMatch[1], 'Header badge should show the real unread count.');
        $this->assertSame('3', str_replace(',', '', $cardMatch[1]), 'Stat card should match the header badge, not silently show 0.');
    }
}
