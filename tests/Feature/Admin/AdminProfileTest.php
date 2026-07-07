<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    public function test_admin_can_view_profile_page(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.profile.edit'))->assertOk();
    }

    public function test_admin_can_update_name_and_email(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->patch(route('admin.profile.update'), [
            'name' => 'New Name',
            'email' => 'newemail@example.com',
        ]);

        $response->assertRedirect();
        $this->assertSame('New Name', $admin->fresh()->name);
        $this->assertSame('newemail@example.com', $admin->fresh()->email);
    }

    public function test_admin_can_update_password(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->put(route('admin.profile.password'), [
            'current_password' => 'password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('new-secure-password', $admin->fresh()->password));
    }

    public function test_admin_cannot_update_password_with_wrong_current_password(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->put(route('admin.profile.password'), [
            'current_password' => 'wrong-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $response->assertSessionHasErrors('current_password');
    }
}
