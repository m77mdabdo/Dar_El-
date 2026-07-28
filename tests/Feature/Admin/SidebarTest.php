<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Admin usability audit finding #3: unbuilt sidebar items looked and
 * behaved like real links until an employee clicked one and hit a dead
 * end. Covers both halves of the fix — the content re-audit (Product
 * Images/Variants shipped as tabs inside product edit this session, so a
 * separate top-level nav entry for either was removed rather than left as
 * a placeholder that would never be filled in; Social Links now points at
 * the real Website settings page, which already has the Facebook/
 * Instagram fields) and the "obviously not a real link" treatment for
 * whatever's still genuinely unbuilt.
 */
class SidebarTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    /**
     * The sidebar's <a> tags spread href/@click/class across separate
     * lines (see sidebar.blade.php), so a plain adjacent-substring check
     * doesn't survive the whitespace between them — a regex tolerant of
     * that is what actually proves this specific href sits on an element
     * carrying dj-admin-nav-sub-link, not just that both strings appear
     * somewhere on the page.
     */
    protected function assertSeeSidebarLink(string $content, string $href): void
    {
        $pattern = '/href="'.preg_quote($href, '/').'"[^>]*class="dj-admin-nav-sub-link/s';
        $this->assertMatchesRegularExpression($pattern, $content);
    }

    /**
     * A bare assertSee() on the label text and a bare assertSee() on the
     * "dj-admin-nav-soon" marker can both pass for reasons that have
     * nothing to do with each other — the label text might just appear
     * elsewhere on the page (the dashboard has its own "Inventory" chart
     * section, for instance), and the marker exists as long as *any*
     * unbuilt item is still on the page. This proves the label is actually
     * inside a <span class="dj-admin-nav-soon">...</span> element.
     */
    protected function assertSidebarItemIsSoon(string $content, string $label): void
    {
        $pattern = '/<span class="dj-admin-nav-soon">\s*<span class="truncate">'.preg_quote($label, '/').'<\/span>/s';
        $this->assertMatchesRegularExpression($pattern, $content);
    }

    public function test_a_real_nav_item_renders_as_a_working_link(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertSeeSidebarLink($response->getContent(), route('admin.products.index'));
    }

    public function test_an_unbuilt_nav_item_renders_as_a_non_clickable_span_with_a_soon_badge(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        // "Payments" (nav.settings_payments, under Settings) is still
        // genuinely unbuilt — rendered as a <span>, never an <a>, so
        // there's no href to visit at all, and carries the Soon badge.
        // ("Wishlist" used to be this test's example, but shipped as
        // Admin\WishlistAnalyticsController — see WishlistAnalyticsTest.)
        $this->assertSidebarItemIsSoon($response->getContent(), __('admin.nav.settings_payments'));
    }

    public function test_product_images_and_variants_are_no_longer_listed_as_separate_soon_placeholders(): void
    {
        // Both shipped this session as tabs inside product edit (see
        // admin/products/edit.blade.php) — a standalone top-level page for
        // either was never actually the plan, so the placeholder entries
        // were removed rather than staying permanently "Soon".
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('nav.product_images');
        $response->assertDontSee('nav.variants');
    }

    public function test_social_links_now_points_at_the_real_settings_page(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertSeeSidebarLink($response->getContent(), route('admin.settings.edit'));

        // "Website" already linked here before this fix — asserting a
        // *second* real sub-link to the same URL is what actually proves
        // "Social Links" specifically is the newly-fixed one, not just
        // that some link to Settings exists (which was already true).
        $pattern = '/href="'.preg_quote(route('admin.settings.edit'), '/').'"[^>]*class="dj-admin-nav-sub-link/s';
        $this->assertSame(2, preg_match_all($pattern, $response->getContent()));
    }

    /**
     * Extracts just the <button class="dj-admin-nav-group-btn">...</button>
     * markup for one group (identified by its visible label text) — not
     * the child items that follow it in the DOM, which have their own Soon
     * badges that would otherwise make this check meaningless (every group
     * with at least one unbuilt child would "contain" a badge somewhere in
     * its subtree regardless of whether the header itself has one).
     */
    protected function extractGroupButtonMarkup(string $html, string $label): string
    {
        $labelPos = strpos($html, $label);
        $this->assertNotFalse($labelPos, "Group label \"{$label}\" not found in the response.");

        $buttonStart = strrpos(substr($html, 0, $labelPos), '<button');
        $buttonEnd = strpos($html, '</button>', $labelPos);

        return substr($html, $buttonStart, $buttonEnd - $buttonStart);
    }

    /**
     * Deliberately stubs config('admin_sidebar') rather than asserting
     * against whichever real group happens to be 100%-unbuilt today —
     * that was "Reports" when this test was first written, but every one
     * of Reports' 5 items shipped as real pages in this same session
     * (Wishlist, Sales, Products, Customers, Inventory), which would have
     * made this test permanently unfixable without either picking a new
     * real group each time one gets finished, or (this fix) testing the
     * actual logic in isolation from the ever-changing live config.
     */
    public function test_a_group_where_every_item_is_still_unbuilt_shows_a_soon_badge_on_the_group_itself(): void
    {
        config(['admin_sidebar' => [
            ['label' => 'nav.dashboard', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'home'],
            ['label' => 'nav.reports', 'icon' => 'chart-pie', 'items' => [
                ['label' => 'nav.reports_sales', 'route' => null],
                ['label' => 'nav.reports_products', 'route' => null],
            ]],
        ]]);
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $buttonMarkup = $this->extractGroupButtonMarkup($response->getContent(), __('admin.nav.reports'));
        $this->assertStringContainsString('dj-admin-soon-badge', $buttonMarkup);
    }

    public function test_a_group_with_real_items_does_not_show_a_soon_badge_on_the_group_itself(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        // "Catalog" (nav.catalog) is now fully real (Products, Categories,
        // Inventory, Reviews) — the group header must NOT carry a Soon
        // badge.
        $buttonMarkup = $this->extractGroupButtonMarkup($response->getContent(), __('admin.nav.catalog'));
        $this->assertStringNotContainsString('dj-admin-soon-badge', $buttonMarkup);
    }

    public function test_inventory_is_now_a_real_working_link_not_a_soon_placeholder(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertSeeSidebarLink($response->getContent(), route('admin.inventory.index'));
    }

    /**
     * "Reports" (nav.reports) is now fully real — Sales, Products,
     * Customers, Wishlist (shared with Marketing > Wishlist), and
     * Inventory all shipped this session — mirroring the Catalog test
     * above against the ACTUAL live config now that it's safe to do so.
     */
    public function test_reports_group_is_now_fully_real_and_does_not_show_a_soon_badge(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $buttonMarkup = $this->extractGroupButtonMarkup($response->getContent(), __('admin.nav.reports'));
        $this->assertStringNotContainsString('dj-admin-soon-badge', $buttonMarkup);
    }
}
