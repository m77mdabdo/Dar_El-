<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_gets_the_same_dashboard_access_as_admin(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::findOrCreate('super_admin', 'web'));

        $response = $this->actingAs($superAdmin)->get('/admin/dashboard');

        $response->assertOk();
    }

    public function test_bare_employee_can_enter_the_admin_panel_but_is_blocked_from_gated_actions(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole(Role::findOrCreate('employee', 'web'));

        // No permissions granted at all — mirrors how the existing 26 test
        // files create a bare 'admin' role with zero seeded permissions,
        // proving hasAdminAccess() degrades safely (403) rather than
        // crashing on an unseeded permission check.
        $this->actingAs($employee)->get('/admin/dashboard')->assertOk();
        $this->actingAs($employee)->post('/admin/products', [])->assertForbidden();
    }

    public function test_customer_still_cannot_enter_the_admin_panel(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(Role::findOrCreate('customer', 'web'));

        $response = $this->actingAs($customer)->get('/admin/dashboard');

        $response->assertForbidden();
    }
}
