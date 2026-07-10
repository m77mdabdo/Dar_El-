<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function makeSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));

        return $user;
    }

    protected function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));

        return $user;
    }

    protected function makeEmployee(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('employee', 'web'));

        return $user;
    }

    public function test_admin_is_forbidden_from_every_user_management_route(): void
    {
        $admin = $this->makeAdmin();
        $target = $this->makeEmployee();

        $this->actingAs($admin)->get('/admin/users')->assertForbidden();
        $this->actingAs($admin)->get('/admin/users/create')->assertForbidden();
        $this->actingAs($admin)->get("/admin/users/{$target->id}/edit")->assertForbidden();
        $this->actingAs($admin)->post('/admin/users', [])->assertForbidden();
    }

    public function test_employee_is_forbidden_from_every_user_management_route(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_does_not_see_the_users_link_in_the_sidebar(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertDontSee('href="'.route('admin.users.index').'"', false);
    }

    public function test_super_admin_can_create_a_super_admin_an_admin_and_an_employee(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        // syncRoles() inside the controller uses Role::findByName() (throws
        // if missing) — pre-create every role this loop will assign, same
        // as every other bare-role test in this suite.
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('employee', 'web');

        foreach (['super_admin', 'admin', 'employee'] as $role) {
            $response = $this->actingAs($superAdmin)->post('/admin/users', [
                'name' => 'New '.$role,
                'email' => $role.'@example.com',
                'phone' => '01000000000',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => $role,
                'is_active' => '1',
            ]);

            $response->assertRedirect(route('admin.users.index'));
            $user = User::where('email', $role.'@example.com')->first();
            $this->assertNotNull($user);
            $this->assertTrue($user->hasRole($role));
        }
    }

    public function test_super_admin_cannot_change_their_own_role(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->put("/admin/users/{$superAdmin->id}", [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'role' => 'admin',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertTrue($superAdmin->fresh()->hasRole('super_admin'));
    }

    public function test_super_admin_cannot_delete_themselves(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->delete("/admin/users/{$superAdmin->id}");

        $response->assertForbidden();
        $this->assertNotNull($superAdmin->fresh());
    }

    /**
     * The controller's "can't delete the last super admin" guard is
     * defense-in-depth (protects against races / future refactors of the
     * self-delete guard) rather than reachable through a single serial
     * HTTP request today: this route requires the actor to themselves be
     * a super_admin, and self-deletion is already unconditionally
     * blocked separately — so whenever an actor validly deletes an
     * *other* super_admin, the actor's own account guarantees at least
     * one remains. This test verifies that reachable positive path (a
     * super admin can delete another one when 2+ exist) plus the
     * self-delete guard already covered above.
     */
    public function test_super_admin_can_delete_another_super_admin_when_more_than_one_exists(): void
    {
        $target = $this->makeSuperAdmin();
        $actor = $this->makeSuperAdmin();

        $response = $this->actingAs($actor)->delete("/admin/users/{$target->id}");

        $response->assertRedirect(route('admin.users.index'));
        $this->assertNull($target->fresh());
        $this->assertNotNull($actor->fresh());
    }

    public function test_employee_permission_checkboxes_persist_and_are_reflected_in_has_admin_access(): void
    {
        Notification::fake();
        $superAdmin = $this->makeSuperAdmin();
        Role::findOrCreate('employee', 'web');
        \Spatie\Permission\Models\Permission::findOrCreate('products.view', 'web');
        \Spatie\Permission\Models\Permission::findOrCreate('orders.view', 'web');

        $this->actingAs($superAdmin)->post('/admin/users', [
            'name' => 'Grantable Employee',
            'email' => 'grantable@example.com',
            'phone' => '01000000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
            'is_active' => '1',
            'permissions' => ['products.view', 'orders.view'],
        ])->assertRedirect(route('admin.users.index'));

        $employee = User::where('email', 'grantable@example.com')->firstOrFail();

        $this->assertTrue($employee->hasAdminAccess('products.view'));
        $this->assertTrue($employee->hasAdminAccess('orders.view'));
        $this->assertFalse($employee->hasAdminAccess('customers.view'));
    }

    public function test_updating_an_employees_permissions_replaces_the_previous_set(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $employee = $this->makeEmployee();
        $employee->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('products.view', 'web'));
        \Spatie\Permission\Models\Permission::findOrCreate('orders.view', 'web');

        $response = $this->actingAs($superAdmin)->put("/admin/users/{$employee->id}", [
            'name' => $employee->name,
            'email' => $employee->email,
            'role' => 'employee',
            'is_active' => '1',
            'permissions' => ['orders.view'],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $employee->refresh();
        $this->assertFalse($employee->hasAdminAccess('products.view'));
        $this->assertTrue($employee->hasAdminAccess('orders.view'));
    }

    public function test_super_admin_cannot_disable_or_force_logout_themselves(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $this->actingAs($superAdmin)->patch("/admin/users/{$superAdmin->id}/toggle-active")->assertForbidden();
        $this->actingAs($superAdmin)->post("/admin/users/{$superAdmin->id}/force-logout")->assertForbidden();
    }

    public function test_super_admin_can_reset_another_users_password(): void
    {
        Notification::fake();
        $superAdmin = $this->makeSuperAdmin();
        $employee = $this->makeEmployee();

        $response = $this->actingAs($superAdmin)->post("/admin/users/{$employee->id}/reset-password");

        $response->assertRedirect();
        $response->assertSessionHas('status');
    }
}
