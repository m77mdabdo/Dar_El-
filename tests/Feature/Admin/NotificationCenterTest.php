<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Notifications\NewCustomerRegistered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    public function test_notifications_index_requires_admin(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(Role::findOrCreate('customer', 'web'));

        $this->actingAs($customer)->get(route('admin.notifications.index'))->assertForbidden();
    }

    public function test_admin_can_view_notifications(): void
    {
        $admin = $this->makeAdmin();
        $admin->notify(new NewCustomerRegistered($admin));

        $response = $this->actingAs($admin)->get(route('admin.notifications.index'));

        $response->assertOk();
        $response->assertSee($admin->name);
    }

    public function test_admin_can_mark_a_single_notification_read(): void
    {
        $admin = $this->makeAdmin();
        $admin->notify(new NewCustomerRegistered($admin));
        $notification = $admin->notifications()->first();

        $response = $this->actingAs($admin)->patchJson(route('admin.notifications.read', $notification->id));

        $response->assertOk()->assertJson(['unread_count' => 0]);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_admin_cannot_mark_another_users_notification_read(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();
        $otherAdmin->notify(new NewCustomerRegistered($otherAdmin));
        $notification = $otherAdmin->notifications()->first();

        $this->actingAs($admin)->patchJson(route('admin.notifications.read', $notification->id))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_admin_can_mark_all_notifications_read(): void
    {
        $admin = $this->makeAdmin();
        $admin->notify(new NewCustomerRegistered($admin));
        $admin->notify(new NewCustomerRegistered($admin));

        $response = $this->actingAs($admin)->patchJson(route('admin.notifications.read-all'));

        $response->assertOk()->assertJson(['unread_count' => 0]);
        $this->assertSame(0, $admin->unreadNotifications()->count());
    }
}
