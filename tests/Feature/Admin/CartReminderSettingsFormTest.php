<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CartReminderSettingsFormTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_admin_can_view_and_update_cart_reminder_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee(__('settings.cart_reminders'));

        $response = $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'cart_reminders_enabled' => '1',
            'cart_reminder_notification_enabled' => '0',
            'cart_reminder_first_delay_hours' => 2,
            'cart_reminder_interval_hours' => 6,
            'cart_max_reminders' => 5,
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('1', Setting::get('cart_reminders_enabled'));
        $this->assertSame('0', Setting::get('cart_reminder_notification_enabled'));
        $this->assertSame('2', Setting::get('cart_reminder_first_delay_hours'));
        $this->assertSame('6', Setting::get('cart_reminder_interval_hours'));
        $this->assertSame('5', Setting::get('cart_max_reminders'));
    }

    public function test_cart_reminder_number_fields_reject_out_of_range_values(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'cart_reminder_first_delay_hours' => 0,
        ])->assertSessionHasErrors('cart_reminder_first_delay_hours');
    }
}
