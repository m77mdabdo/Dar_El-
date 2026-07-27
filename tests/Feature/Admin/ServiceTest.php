<?php

namespace Tests\Feature\Admin;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Services was genuinely new — no prior model/table existed. The public
 * /services page was a hardcoded grid of 6 service cards (icon, title,
 * description). This covers the admin CRUD, drag-reorder, and the public
 * page's fallback behavior — same opt-in rule as Hero Banners/
 * Testimonials/FAQ: zero admin-created services means the page looks
 * exactly as it always has (Service::fallbackList()), not blank.
 *
 * Unlike those other three features, /services is NOT cached anywhere
 * (PageController::services() queries fresh every request, matching
 * about()/returnPolicy()) — so there's no cache-busting behavior to test
 * here, deliberately.
 */
class ServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    protected function makeEmployee(): User
    {
        $employee = User::factory()->create();
        $employee->assignRole(Role::findOrCreate('employee', 'web'));

        return $employee;
    }

    protected function grant(User $user, string $permission): void
    {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    protected function makeService(array $overrides = []): Service
    {
        return Service::create(array_merge([
            'title_ar' => 'خدمة تجريبية', 'title_en' => 'A Test Service',
            'description_ar' => 'وصف تجريبي.', 'description_en' => 'A test description.',
            'icon' => 'star', 'is_active' => true, 'sort_order' => 0,
        ], $overrides));
    }

    // ---------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------

    public function test_a_bare_employee_without_the_permission_is_forbidden(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee)->get(route('admin.services.index'))->assertForbidden();
    }

    public function test_an_employee_granted_pages_manage_can_view_it(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'pages.manage');
        $this->makeService(['title_ar' => 'مرئي للموظف', 'title_en' => 'Employee Visible Service']);

        $response = $this->actingAs($employee)->get(route('admin.services.index'));

        $response->assertOk();
        $response->assertSee('مرئي للموظف');
    }

    public function test_admin_can_view_it_regardless_of_seeded_permissions(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.services.index'))->assertOk();
    }

    // ---------------------------------------------------------------
    // Create / update / delete / toggle
    // ---------------------------------------------------------------

    public function test_admin_can_create_a_service(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('admin.services.store'), [
            'title_ar' => 'خدمة توصيل سريع', 'title_en' => 'Express Delivery',
            'description_ar' => 'توصيل خلال 24 ساعة.', 'description_en' => 'Delivery within 24 hours.',
            'icon' => 'truck_missing_should_fail_or_fallback', 'is_active' => '1',
        ]);

        // 'truck' isn't a valid icon key (bag is), so this should fail
        // validation rather than silently storing an invalid icon.
        $response->assertSessionHasErrors('icon');
        $this->assertDatabaseMissing('services', ['title_en' => 'Express Delivery']);
    }

    public function test_admin_can_create_a_service_with_a_valid_icon(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('admin.services.store'), [
            'title_ar' => 'خدمة توصيل سريع', 'title_en' => 'Express Delivery',
            'description_ar' => 'توصيل خلال 24 ساعة.', 'description_en' => 'Delivery within 24 hours.',
            'icon' => 'bag', 'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseHas('services', ['title_en' => 'Express Delivery', 'icon' => 'bag']);
    }

    public function test_all_four_text_fields_and_icon_are_required(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('admin.services.store'), []);

        $response->assertSessionHasErrors(['title_ar', 'title_en', 'description_ar', 'description_en', 'icon']);
    }

    public function test_admin_can_update_a_service(): void
    {
        $admin = $this->makeAdmin();
        $service = $this->makeService();

        $response = $this->actingAs($admin)->put(route('admin.services.update', $service), [
            'title_ar' => $service->title_ar, 'title_en' => 'Updated Service',
            'description_ar' => $service->description_ar, 'description_en' => 'Updated description.',
            'icon' => 'gift',
        ]);

        $response->assertRedirect(route('admin.services.index'));
        $this->assertSame('Updated Service', $service->fresh()->title_en);
        $this->assertSame('gift', $service->fresh()->icon);
    }

    public function test_admin_can_delete_a_service(): void
    {
        $admin = $this->makeAdmin();
        $service = $this->makeService();

        $response = $this->actingAs($admin)->delete(route('admin.services.destroy', $service));

        $response->assertRedirect(route('admin.services.index'));
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_toggle_active_flips_the_service(): void
    {
        $admin = $this->makeAdmin();
        $service = $this->makeService(['is_active' => true]);

        $this->actingAs($admin)->patch(route('admin.services.toggle-active', $service));

        $this->assertFalse($service->fresh()->is_active);
    }

    // ---------------------------------------------------------------
    // Reorder
    // ---------------------------------------------------------------

    public function test_reorder_persists_the_new_sort_order(): void
    {
        $admin = $this->makeAdmin();
        $first = $this->makeService(['title_en' => 'First', 'sort_order' => 0]);
        $second = $this->makeService(['title_en' => 'Second', 'sort_order' => 1]);

        $response = $this->actingAs($admin)->patchJson(route('admin.services.reorder'), [
            'ids' => [$second->id, $first->id],
        ]);

        $response->assertOk();
        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);
    }

    // ---------------------------------------------------------------
    // Public /services page
    // ---------------------------------------------------------------

    public function test_services_page_shows_the_default_fallback_services_when_none_exist(): void
    {
        $response = $this->get(route('services'));

        $response->assertOk();
        $response->assertSee(__('Custom Tailoring'));
        $response->assertSee(__('Flexible Payment Options'));
    }

    public function test_services_page_shows_real_active_services_instead_of_the_default(): void
    {
        $this->makeService(['title_ar' => 'Real Services Page Service', 'title_en' => 'Real Services Page Service', 'is_active' => true]);

        $response = $this->get(route('services'));

        $response->assertOk();
        $response->assertSee('Real Services Page Service');
        $response->assertDontSee(__('Custom Tailoring'));
    }

    public function test_services_page_excludes_inactive_services(): void
    {
        $this->makeService(['title_ar' => 'Inactive Service', 'title_en' => 'Inactive Service', 'is_active' => false]);

        $response = $this->get(route('services'));

        $response->assertOk();
        $response->assertDontSee('Inactive Service');
        // Falls back to defaults since the only real row is inactive.
        $response->assertSee(__('Custom Tailoring'));
    }

    public function test_services_page_renders_the_real_services_icon(): void
    {
        $this->makeService(['title_ar' => 'Gift Wrap Extra', 'title_en' => 'Gift Wrap Extra', 'icon' => 'gift', 'is_active' => true]);

        $response = $this->get(route('services'));

        $response->assertOk();
        // The gift icon's distinctive path fragment.
        $response->assertSee('M20 12v9H4v-9', false);
    }
}
