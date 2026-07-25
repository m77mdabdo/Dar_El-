<?php

namespace Tests\Feature\Admin;

use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * FAQ was genuinely new — no prior model/table existed, only a reusable
 * partials.faq-accordion component fed by hardcoded arrays on the
 * homepage and the return-policy page. This covers the admin CRUD, the
 * new public /faq page, and the homepage teaser — both falling back to
 * the exact original hardcoded 5 questions (Faq::fallbackList()) when
 * no admin has added a real one yet, same opt-in rule as Hero Banners
 * and Testimonials.
 */
class FaqTest extends TestCase
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

    protected function makeFaq(array $overrides = []): Faq
    {
        return Faq::create(array_merge([
            'question_ar' => 'سؤال تجريبي؟', 'question_en' => 'A Test Question?',
            'answer_ar' => 'إجابة تجريبية.', 'answer_en' => 'A test answer.',
            'is_active' => true, 'sort_order' => 0,
        ], $overrides));
    }

    // ---------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------

    public function test_a_bare_employee_without_the_permission_is_forbidden(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee)->get(route('admin.faqs.index'))->assertForbidden();
    }

    public function test_an_employee_granted_pages_manage_can_view_it(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'pages.manage');
        $this->makeFaq(['question_ar' => 'مرئي للموظف', 'question_en' => 'Employee Visible Question']);

        $response = $this->actingAs($employee)->get(route('admin.faqs.index'));

        $response->assertOk();
        $response->assertSee('مرئي للموظف');
    }

    public function test_admin_can_view_it_regardless_of_seeded_permissions(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.faqs.index'))->assertOk();
    }

    // ---------------------------------------------------------------
    // Create / update / delete / toggle
    // ---------------------------------------------------------------

    public function test_admin_can_create_a_faq(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('admin.faqs.store'), [
            'question_ar' => 'هل يوجد شحن دولي؟', 'question_en' => 'Do you ship internationally?',
            'answer_ar' => 'ليس حاليًا.', 'answer_en' => 'Not currently.',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.faqs.index'));
        $this->assertDatabaseHas('faqs', ['question_en' => 'Do you ship internationally?', 'answer_en' => 'Not currently.']);
    }

    public function test_all_four_text_fields_are_required(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('admin.faqs.store'), []);

        $response->assertSessionHasErrors(['question_ar', 'question_en', 'answer_ar', 'answer_en']);
    }

    public function test_admin_can_update_a_faq(): void
    {
        $admin = $this->makeAdmin();
        $faq = $this->makeFaq();

        $response = $this->actingAs($admin)->put(route('admin.faqs.update', $faq), [
            'question_ar' => $faq->question_ar, 'question_en' => 'Updated Question?',
            'answer_ar' => $faq->answer_ar, 'answer_en' => 'Updated answer.',
        ]);

        $response->assertRedirect(route('admin.faqs.index'));
        $this->assertSame('Updated Question?', $faq->fresh()->question_en);
    }

    public function test_admin_can_delete_a_faq(): void
    {
        $admin = $this->makeAdmin();
        $faq = $this->makeFaq();

        $response = $this->actingAs($admin)->delete(route('admin.faqs.destroy', $faq));

        $response->assertRedirect(route('admin.faqs.index'));
        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    public function test_toggle_active_flips_the_faq(): void
    {
        $admin = $this->makeAdmin();
        $faq = $this->makeFaq(['is_active' => true]);

        $this->actingAs($admin)->patch(route('admin.faqs.toggle-active', $faq));

        $this->assertFalse($faq->fresh()->is_active);
    }

    // ---------------------------------------------------------------
    // Reorder
    // ---------------------------------------------------------------

    public function test_reorder_persists_the_new_sort_order(): void
    {
        $admin = $this->makeAdmin();
        $first = $this->makeFaq(['question_en' => 'First', 'sort_order' => 0]);
        $second = $this->makeFaq(['question_en' => 'Second', 'sort_order' => 1]);

        $response = $this->actingAs($admin)->patchJson(route('admin.faqs.reorder'), [
            'ids' => [$second->id, $first->id],
        ]);

        $response->assertOk();
        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);
    }

    // ---------------------------------------------------------------
    // Public /faq page
    // ---------------------------------------------------------------

    public function test_faq_page_shows_the_default_fallback_questions_when_none_exist(): void
    {
        $response = $this->get(route('faq'));

        $response->assertOk();
        $response->assertSee('كام مدة التوصيل؟');
    }

    public function test_faq_page_shows_real_active_faqs_instead_of_the_default(): void
    {
        $this->makeFaq(['question_ar' => 'Real FAQ Page Question', 'question_en' => 'Real FAQ Page Question', 'is_active' => true]);

        $response = $this->get(route('faq'));

        $response->assertOk();
        $response->assertSee('Real FAQ Page Question');
        $response->assertDontSee('كام مدة التوصيل؟');
    }

    public function test_faq_page_excludes_inactive_faqs(): void
    {
        $this->makeFaq(['question_ar' => 'Inactive Question', 'question_en' => 'Inactive Question', 'is_active' => false]);

        $response = $this->get(route('faq'));

        $response->assertOk();
        $response->assertDontSee('Inactive Question');
        // Falls back to defaults since the only real row is inactive.
        $response->assertSee('كام مدة التوصيل؟');
    }

    // ---------------------------------------------------------------
    // Homepage teaser
    // ---------------------------------------------------------------

    public function test_homepage_shows_default_fallback_faqs_when_none_exist(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('كام مدة التوصيل؟');
    }

    public function test_homepage_shows_real_active_faqs_instead_of_the_default(): void
    {
        $this->makeFaq(['question_ar' => 'Real Homepage FAQ Question', 'question_en' => 'Real Homepage FAQ Question', 'is_active' => true]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Real Homepage FAQ Question');
        $response->assertDontSee('كام مدة التوصيل؟');
    }

    public function test_homepage_links_to_the_full_faq_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(route('faq'), false);
    }

    // ---------------------------------------------------------------
    // Cache-busting
    // ---------------------------------------------------------------

    public function test_creating_a_faq_via_admin_immediately_changes_the_live_homepage(): void
    {
        \Illuminate\Support\Facades\Cache::flush();
        $admin = $this->makeAdmin();

        // Prime the "no real FAQs" cached state first, same as a real
        // visitor hitting the homepage before an admin ever adds one —
        // proves Faq::booted()'s cache-busting actually fires.
        $this->get('/')->assertSee('كام مدة التوصيل؟');

        $this->actingAs($admin)->post(route('admin.faqs.store'), [
            'question_ar' => 'Instant Live Question', 'question_en' => 'Instant Live Question',
            'answer_ar' => 'Instant live answer', 'answer_en' => 'Instant live answer',
            'is_active' => '1',
        ]);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Instant Live Question');
    }
}
