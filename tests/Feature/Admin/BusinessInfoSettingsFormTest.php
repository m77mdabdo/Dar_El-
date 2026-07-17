<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessInfoSettingsFormTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_admin_can_view_and_update_business_address_and_hours(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee(__('settings.business_address'))
            ->assertSee(__('settings.business_hours'));

        $response = $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'business_address' => '123 Tahrir Street, Cairo, Egypt',
            'business_hours' => 'Mo-Sa 10:00-22:00',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('123 Tahrir Street, Cairo, Egypt', Setting::get('business_address'));
        $this->assertSame('Mo-Sa 10:00-22:00', Setting::get('business_hours'));
    }

    public function test_business_address_and_hours_are_optional(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->patch(route('admin.settings.update'), []);

        $response->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_business_address_and_hours_respect_max_length(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'business_address' => str_repeat('a', 501),
            'business_hours' => str_repeat('a', 256),
        ]);

        $response->assertSessionHasErrors(['business_address', 'business_hours']);
    }
}
