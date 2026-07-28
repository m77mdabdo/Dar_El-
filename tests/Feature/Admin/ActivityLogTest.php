<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function makeSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));

        return $user;
    }

    protected function makeSubject(): Category
    {
        return Category::create(['name_ar' => 'ف', 'name_en' => 'Cat', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
    }

    // ---------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------

    public function test_super_admin_can_view_the_activity_log(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $this->actingAs($superAdmin)->get(route('admin.activity-log.index'))->assertOk();
    }

    public function test_admin_is_forbidden_and_does_not_see_the_link(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $this->actingAs($admin)->get(route('admin.activity-log.index'))->assertForbidden();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertDontSee('href="'.route('admin.activity-log.index').'"', false);
    }

    public function test_employee_is_forbidden(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole(Role::findOrCreate('employee', 'web'));

        $this->actingAs($employee)->get(route('admin.activity-log.index'))->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Data correctness
    // ---------------------------------------------------------------

    public function test_it_lists_real_activity_log_entries(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $category = $this->makeSubject();
        ActivityLog::record('created', $category, 'Created category '.$category->name_en);

        $response = $this->actingAs($superAdmin)->get(route('admin.activity-log.index'));

        $response->assertOk();
        $response->assertSee('Created category Cat');
    }

    public function test_entries_from_a_deleted_user_show_as_system_not_a_crash(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $staff = User::factory()->create();
        $staff->assignRole(Role::findOrCreate('admin', 'web'));
        $category = $this->makeSubject();

        $this->actingAs($staff);
        ActivityLog::record('created', $category, 'Created while this account still existed');
        $staff->delete();

        $response = $this->actingAs($superAdmin)->get(route('admin.activity-log.index'));

        $response->assertOk();
        $response->assertSee(__('activity_log.system'));
    }

    public function test_filtering_by_action_narrows_the_list(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $category = $this->makeSubject();
        ActivityLog::record('created', $category, 'Created entry');
        ActivityLog::record('deleted', $category, 'Deleted entry');

        $response = $this->actingAs($superAdmin)->get(route('admin.activity-log.index', ['action' => 'created']));

        $response->assertOk();
        $response->assertSee('Created entry');
        $response->assertDontSee('Deleted entry');
    }

    public function test_filtering_by_subject_type_narrows_the_list(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $category = $this->makeSubject();
        ActivityLog::record('created', $category, 'Category entry');

        $user = User::factory()->create();
        ActivityLog::record('created', $user, 'User entry');

        $response = $this->actingAs($superAdmin)->get(route('admin.activity-log.index', ['subject_type' => \App\Models\Category::class]));

        $response->assertOk();
        $response->assertSee('Category entry');
        $response->assertDontSee('User entry');
    }

    public function test_filtering_by_user_narrows_the_list(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $category = $this->makeSubject();

        $staffA = User::factory()->create(['name' => 'Staff A']);
        $this->actingAs($staffA);
        ActivityLog::record('created', $category, 'Entry by Staff A');

        $staffB = User::factory()->create(['name' => 'Staff B']);
        $this->actingAs($staffB);
        ActivityLog::record('created', $category, 'Entry by Staff B');

        $response = $this->actingAs($superAdmin)->get(route('admin.activity-log.index', ['user_id' => $staffA->id]));

        $response->assertOk();
        $response->assertSee('Entry by Staff A');
        $response->assertDontSee('Entry by Staff B');
    }

    public function test_a_custom_date_range_is_respected(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $category = $this->makeSubject();

        ActivityLog::record('created', $category, 'In range entry');
        $inRange = ActivityLog::latest()->first();
        $inRange->forceFill(['created_at' => '2026-01-15 12:00:00'])->save();

        ActivityLog::record('created', $category, 'Out of range entry');
        $outOfRange = ActivityLog::latest()->first();
        $outOfRange->forceFill(['created_at' => '2026-03-01 12:00:00'])->save();

        $response = $this->actingAs($superAdmin)->get(route('admin.activity-log.index', [
            'from' => '2026-01-01', 'to' => '2026-01-31',
        ]));

        $response->assertOk();
        $response->assertSee('In range entry');
        $response->assertDontSee('Out of range entry');
    }

    public function test_action_and_subject_dropdowns_reflect_only_whats_actually_logged(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $category = $this->makeSubject();
        ActivityLog::record('created', $category);

        $response = $this->actingAs($superAdmin)->get(route('admin.activity-log.index'));

        $response->assertOk();
        $response->assertSee(__('activity_log.actions.created'));
        // 'deleted' never happened in this test, so its label should not
        // be forced into the filter dropdown.
        $response->assertDontSee(__('activity_log.actions.deleted'));
    }
}
