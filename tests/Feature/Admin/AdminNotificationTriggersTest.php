<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewContactMessage;
use App\Notifications\NewCustomerRegistered;
use App\Notifications\NewsletterSubscribed;
use App\Notifications\OrderCancelled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminNotificationTriggersTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    public function test_registration_notifies_admins_of_new_customer(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();

        $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '01000000000',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        Notification::assertSentTo($admin, NewCustomerRegistered::class);
    }

    public function test_contact_form_notifies_admins(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();

        $this->post('/contact', [
            'name' => 'Sara',
            'email' => 'sara@example.com',
            'message' => 'Hello there',
        ]);

        Notification::assertSentTo($admin, NewContactMessage::class);
    }

    public function test_newsletter_signup_notifies_admins(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();

        $this->post('/newsletter', ['email' => 'subscriber@example.com']);

        Notification::assertSentTo($admin, NewsletterSubscribed::class);
    }

    public function test_cancelling_an_order_notifies_admins(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();

        $category = Category::create(['name_ar' => 'ع', 'name_en' => 'Cat', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $product = Product::create(['category_id' => $category->id, 'name_ar' => 'م', 'name_en' => 'Prod', 'slug' => 'prod-'.uniqid(), 'price' => 500, 'is_active' => true, 'is_featured' => false]);
        $product->sizes()->create(['size' => 'M', 'stock' => 5]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-'.uniqid(),
            'customer_name' => 'Test', 'customer_email' => 'test@example.com', 'customer_phone' => '010',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'x',
            'subtotal' => 500, 'shipping_fee' => 0, 'total' => 500, 'status' => 'pending', 'payment_method' => 'cod',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name_en, 'size' => 'M', 'price' => 500, 'quantity' => 1]);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'cancelled']);

        Notification::assertSentTo($admin, OrderCancelled::class);
    }
}
