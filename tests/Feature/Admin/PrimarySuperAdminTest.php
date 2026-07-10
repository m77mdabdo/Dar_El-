<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\PrimarySuperAdminSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrimarySuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function makeSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));

        return $user;
    }

    public function test_the_configured_primary_super_admin_email_matches_the_required_account(): void
    {
        $this->assertSame('creativedigitalmohamedabdo@gmail.com', config('primary_super_admin.email'));
    }

    public function test_seeder_creates_the_primary_super_admin_when_it_does_not_exist(): void
    {
        config(['primary_super_admin.email' => 'primary@example.com']);
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->assertNull(User::where('email', 'primary@example.com')->first());

        $this->seed(PrimarySuperAdminSeeder::class);

        $primary = User::where('email', 'primary@example.com')->first();
        $this->assertNotNull($primary);
        $this->assertTrue($primary->hasRole('super_admin'));
        $this->assertNotNull($primary->email_verified_at);
        $this->assertFalse($primary->isDisabled());
        $this->assertGreaterThan(0, $primary->getAllPermissions()->count());
        $this->assertSame(
            Permission::where('guard_name', 'web')->count(),
            $primary->permissions()->count()
        );
    }

    public function test_seeder_promotes_an_existing_account_to_super_admin_with_full_permissions_without_touching_others(): void
    {
        config(['primary_super_admin.email' => 'primary@example.com']);
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $existing = User::factory()->create(['email' => 'primary@example.com']);
        $existing->assignRole('customer');
        $existing->forceFill(['disabled_at' => now()])->save();

        $untouched = User::factory()->create(['email' => 'someone-else@example.com']);
        $untouched->assignRole('customer');

        $this->seed(PrimarySuperAdminSeeder::class);

        $existing->refresh();
        $this->assertTrue($existing->hasRole('super_admin'));
        $this->assertFalse($existing->hasRole('customer'));
        $this->assertFalse($existing->isDisabled());
        $this->assertSame(
            Permission::where('guard_name', 'web')->count(),
            $existing->permissions()->count()
        );

        $untouched->refresh();
        $this->assertTrue($untouched->hasRole('customer'));
        $this->assertFalse($untouched->hasRole('super_admin'));
    }

    public function test_another_super_admin_cannot_change_the_primary_super_admins_role(): void
    {
        config(['primary_super_admin.email' => 'primary@example.com']);
        Role::findOrCreate('admin', 'web');
        $primary = $this->makeSuperAdmin();
        $primary->forceFill(['email' => 'primary@example.com'])->save();
        $actor = $this->makeSuperAdmin();

        $response = $this->actingAs($actor)->put("/admin/users/{$primary->id}", [
            'name' => $primary->name,
            'email' => $primary->email,
            'role' => 'admin',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertTrue($primary->fresh()->hasRole('super_admin'));
    }

    public function test_another_super_admin_cannot_change_the_primary_super_admins_email(): void
    {
        config(['primary_super_admin.email' => 'primary@example.com']);
        $primary = $this->makeSuperAdmin();
        $primary->forceFill(['email' => 'primary@example.com'])->save();
        $actor = $this->makeSuperAdmin();

        $response = $this->actingAs($actor)->put("/admin/users/{$primary->id}", [
            'name' => $primary->name,
            'email' => 'changed@example.com',
            'role' => 'super_admin',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame('primary@example.com', $primary->fresh()->email);
    }

    public function test_another_super_admin_cannot_delete_the_primary_super_admin(): void
    {
        config(['primary_super_admin.email' => 'primary@example.com']);
        $primary = $this->makeSuperAdmin();
        $primary->forceFill(['email' => 'primary@example.com'])->save();
        $actor = $this->makeSuperAdmin();

        $response = $this->actingAs($actor)->delete("/admin/users/{$primary->id}");

        $response->assertForbidden();
        $this->assertNotNull($primary->fresh());
    }

    public function test_another_super_admin_cannot_disable_the_primary_super_admin(): void
    {
        config(['primary_super_admin.email' => 'primary@example.com']);
        $primary = $this->makeSuperAdmin();
        $primary->forceFill(['email' => 'primary@example.com'])->save();
        $actor = $this->makeSuperAdmin();

        $response = $this->actingAs($actor)->patch("/admin/users/{$primary->id}/toggle-active");

        $response->assertForbidden();
        $this->assertFalse($primary->fresh()->isDisabled());
    }

    public function test_creating_a_user_with_the_primary_email_via_the_ui_forces_super_admin_and_full_permissions(): void
    {
        config(['primary_super_admin.email' => 'primary@example.com']);
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('employee', 'web');
        Permission::firstOrCreate(['name' => 'products.view', 'guard_name' => 'web']);
        $actor = $this->makeSuperAdmin();

        $response = $this->actingAs($actor)->post('/admin/users', [
            'name' => 'Sneaky Attempt',
            'email' => 'primary@example.com',
            'phone' => '01000000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'employee',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $primary = User::where('email', 'primary@example.com')->firstOrFail();
        $this->assertTrue($primary->hasRole('super_admin'));
        $this->assertFalse($primary->hasRole('employee'));
        $this->assertTrue($primary->hasAdminAccess('products.view'));
    }
}
