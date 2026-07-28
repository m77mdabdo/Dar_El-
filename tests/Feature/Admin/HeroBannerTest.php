<?php

namespace Tests\Feature\Admin;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The homepage hero (image + headline + CTA button) used to be entirely
 * hardcoded in home.blade.php, with the Banner model/table already real
 * but never actually read for type=hero anywhere (Banner::TYPE_HERO was
 * a dead constant). This admin screen is the first thing that ever
 * writes hero-type Banner rows — DemoBannerSeeder deliberately never
 * seeds them (see its own comment about a past incident where an
 * earlier version silently swapped a real store's homepage to a demo
 * slider), so these tests also cover that the homepage only changes
 * once a real admin explicitly creates AND activates one.
 */
class HeroBannerTest extends TestCase
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

    protected function makeHeroBanner(array $overrides = []): Banner
    {
        return Banner::create(array_merge([
            'type' => Banner::TYPE_HERO,
            'title_ar' => 'عنوان البانر', 'title_en' => 'Banner Title',
            'subtitle_ar' => null, 'subtitle_en' => null,
            'cta_text_ar' => null, 'cta_text_en' => null,
            'link_url' => null,
            'image' => 'banners/existing.webp',
            'is_active' => true, 'sort_order' => 0,
        ], $overrides));
    }

    // ---------------------------------------------------------------
    // Access control
    // ---------------------------------------------------------------

    public function test_a_bare_employee_without_the_permission_is_forbidden(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee)->get(route('admin.hero-banners.index'))->assertForbidden();
    }

    public function test_an_employee_granted_banners_manage_can_view_it(): void
    {
        $employee = $this->makeEmployee();
        $this->grant($employee, 'banners.manage');
        $this->makeHeroBanner(['title_ar' => 'مرئي للموظف', 'title_en' => 'Employee Visible Banner']);

        $response = $this->actingAs($employee)->get(route('admin.hero-banners.index'));

        $response->assertOk();
        $response->assertSee('مرئي للموظف');
    }

    public function test_admin_can_view_it_regardless_of_seeded_permissions(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.hero-banners.index'))->assertOk();
    }

    // ---------------------------------------------------------------
    // Listing — scoped to type=hero only
    // ---------------------------------------------------------------

    public function test_index_only_lists_hero_type_banners_not_offer_or_collection(): void
    {
        $admin = $this->makeAdmin();
        $this->makeHeroBanner(['title_ar' => 'بانر رئيسي', 'title_en' => 'Real Hero Banner']);
        Banner::create([
            'type' => Banner::TYPE_OFFER,
            'title_ar' => 'عرض', 'title_en' => 'An Offer Banner',
            'image' => 'banners/offer.webp', 'is_active' => true, 'sort_order' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.hero-banners.index'));

        $response->assertOk();
        $response->assertSee('بانر رئيسي');
        $response->assertDontSee('An Offer Banner');
    }

    // ---------------------------------------------------------------
    // Create / update
    // ---------------------------------------------------------------

    public function test_admin_can_create_a_hero_banner_with_an_image(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('admin.hero-banners.store'), [
            'title_ar' => 'عنوان جديد', 'title_en' => 'New Hero Banner',
            'subtitle_en' => 'A subtitle', 'subtitle_ar' => 'نص فرعي',
            'cta_text_en' => 'Shop Now', 'cta_text_ar' => 'تسوقي الآن',
            'link_url' => '/shop',
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('hero.jpg'),
        ]);

        $response->assertRedirect(route('admin.hero-banners.index'));
        $this->assertDatabaseHas('banners', [
            'title_en' => 'New Hero Banner', 'type' => Banner::TYPE_HERO,
            'cta_text_en' => 'Shop Now', 'link_url' => '/shop',
        ]);
    }

    /**
     * link_url previously accepted any string — a javascript: URI would
     * execute in a storefront visitor's browser the moment they clicked
     * the hero CTA (2026-07 audit finding, stored XSS reachable by any
     * banners.manage account). SafeLinkUrl closes this at the same
     * validation layer used for every other field here.
     */
    public function test_a_javascript_uri_link_url_is_rejected(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('admin.hero-banners.store'), [
            'title_ar' => 'عنوان جديد', 'title_en' => 'New Hero Banner',
            'link_url' => 'javascript:alert(document.cookie)',
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('hero.jpg'),
        ]);

        $response->assertSessionHasErrors('link_url');
        $this->assertDatabaseMissing('banners', ['title_en' => 'New Hero Banner']);
    }

    public function test_image_is_required_when_creating(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('admin.hero-banners.store'), [
            'title_ar' => 'عنوان', 'title_en' => 'No Image Banner',
        ]);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseMissing('banners', ['title_en' => 'No Image Banner']);
    }

    public function test_image_is_not_required_when_updating_and_existing_image_is_kept(): void
    {
        $admin = $this->makeAdmin();
        $banner = $this->makeHeroBanner(['image' => 'banners/keep-me.webp']);

        $response = $this->actingAs($admin)->put(route('admin.hero-banners.update', $banner), [
            'title_ar' => $banner->title_ar, 'title_en' => 'Updated Without New Image',
        ]);

        $response->assertRedirect(route('admin.hero-banners.index'));
        $this->assertSame('banners/keep-me.webp', $banner->fresh()->image);
        $this->assertSame('Updated Without New Image', $banner->fresh()->title_en);
    }

    public function test_uploading_a_new_image_on_update_replaces_the_old_one(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banners/old.webp', 'fake-old-contents');
        $admin = $this->makeAdmin();
        $banner = $this->makeHeroBanner(['image' => 'banners/old.webp']);

        $this->actingAs($admin)->put(route('admin.hero-banners.update', $banner), [
            'title_ar' => $banner->title_ar, 'title_en' => $banner->title_en,
            'image' => UploadedFile::fake()->image('new.jpg'),
        ]);

        $this->assertNotSame('banners/old.webp', $banner->fresh()->image);
        Storage::disk('public')->assertMissing('banners/old.webp');
    }

    // ---------------------------------------------------------------
    // Delete / toggle
    // ---------------------------------------------------------------

    public function test_admin_can_delete_a_hero_banner(): void
    {
        $admin = $this->makeAdmin();
        $banner = $this->makeHeroBanner();

        $response = $this->actingAs($admin)->delete(route('admin.hero-banners.destroy', $banner));

        $response->assertRedirect(route('admin.hero-banners.index'));
        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
    }

    public function test_toggle_active_flips_the_banner(): void
    {
        $admin = $this->makeAdmin();
        $banner = $this->makeHeroBanner(['is_active' => true]);

        $this->actingAs($admin)->patch(route('admin.hero-banners.toggle-active', $banner));

        $this->assertFalse($banner->fresh()->is_active);
    }

    // ---------------------------------------------------------------
    // Reorder — scoped to type=hero
    // ---------------------------------------------------------------

    public function test_reorder_persists_the_new_sort_order(): void
    {
        $admin = $this->makeAdmin();
        $first = $this->makeHeroBanner(['title_en' => 'First', 'sort_order' => 0]);
        $second = $this->makeHeroBanner(['title_en' => 'Second', 'sort_order' => 1]);

        $response = $this->actingAs($admin)->patchJson(route('admin.hero-banners.reorder'), [
            'ids' => [$second->id, $first->id],
        ]);

        $response->assertOk();
        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);
    }

    public function test_reorder_rejects_ids_belonging_to_a_non_hero_banner(): void
    {
        $admin = $this->makeAdmin();
        $offerBanner = Banner::create([
            'type' => Banner::TYPE_OFFER, 'title_ar' => 'عرض', 'title_en' => 'Offer',
            'image' => 'banners/offer.webp', 'is_active' => true, 'sort_order' => 0,
        ]);

        $response = $this->actingAs($admin)->patchJson(route('admin.hero-banners.reorder'), [
            'ids' => [$offerBanner->id],
        ]);

        $response->assertStatus(422);
    }

    // ---------------------------------------------------------------
    // Real effect on the live homepage
    // ---------------------------------------------------------------

    public function test_homepage_shows_default_hardcoded_hero_when_no_hero_banner_exists(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('تسوّقي المجموعة');
    }

    public function test_homepage_ignores_an_inactive_hero_banner(): void
    {
        $this->makeHeroBanner(['title_en' => 'Inactive Hero', 'title_ar' => 'بانر غير مفعل', 'is_active' => false]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('بانر غير مفعل');
        $response->assertSee('تسوّقي المجموعة');
    }

    public function test_homepage_renders_the_active_hero_banners_own_content_instead_of_the_default(): void
    {
        $this->makeHeroBanner([
            'title_ar' => 'مجموعة الصيف الجديدة', 'title_en' => 'New Summer Collection',
            'subtitle_ar' => 'وصل حديثًا', 'subtitle_en' => 'Just Arrived',
            'cta_text_ar' => 'تسوقي الآن', 'cta_text_en' => 'Shop Now',
            'link_url' => '/shop?collection=summer',
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('مجموعة الصيف الجديدة');
        $response->assertSee('وصل حديثًا');
        $response->assertSee('تسوقي الآن');
        $response->assertSee('/shop?collection=summer', false);
    }

    public function test_the_first_active_hero_banner_by_sort_order_wins_when_multiple_exist(): void
    {
        $this->makeHeroBanner(['title_ar' => 'الثاني', 'title_en' => 'Second Priority', 'sort_order' => 5]);
        $this->makeHeroBanner(['title_ar' => 'الأول', 'title_en' => 'First Priority', 'sort_order' => 1]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('الأول');
        $response->assertDontSee('الثاني');
    }

    public function test_creating_a_hero_banner_via_the_admin_screen_immediately_changes_the_live_homepage(): void
    {
        Storage::fake('public');
        Cache::flush();
        $admin = $this->makeAdmin();

        // Prime the cache with the "no hero banner" state, same as a real
        // visitor hitting the homepage before an admin ever touches this
        // screen — proves Banner::booted()'s cache-busting actually fires,
        // not just that a fresh/uncached request happens to be correct.
        $this->get('/')->assertSee('تسوّقي المجموعة');

        $this->actingAs($admin)->post(route('admin.hero-banners.store'), [
            'title_ar' => 'بانر فوري', 'title_en' => 'Instant Live Banner',
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('hero.jpg'),
        ]);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('بانر فوري');
    }
}
