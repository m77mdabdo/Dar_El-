<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesAndPermissionsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function makeSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));

        return $user;
    }

    public function test_super_admin_can_view_roles_and_permissions_pages(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('employee', 'web');
        Role::findOrCreate('customer', 'web');

        $this->actingAs($superAdmin)->get('/admin/roles')->assertOk();
        $this->actingAs($superAdmin)->get('/admin/permissions')->assertOk();

        $adminRole = Role::findByName('admin', 'web');
        $this->actingAs($superAdmin)->get("/admin/roles/{$adminRole->id}")->assertOk();
    }

    public function test_admin_is_forbidden_from_roles_and_permissions_pages_and_does_not_see_the_links(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $this->actingAs($admin)->get('/admin/roles')->assertForbidden();
        $this->actingAs($admin)->get('/admin/permissions')->assertForbidden();

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();
        $response->assertDontSee('href="'.route('admin.roles.index').'"', false);
        $response->assertDontSee('href="'.route('admin.permissions.index').'"', false);
    }

    public function test_employee_is_forbidden_from_roles_and_permissions_pages(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole(Role::findOrCreate('employee', 'web'));

        $this->actingAs($employee)->get('/admin/roles')->assertForbidden();
        $this->actingAs($employee)->get('/admin/permissions')->assertForbidden();
    }
}
