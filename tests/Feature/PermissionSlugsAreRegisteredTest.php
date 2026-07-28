<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Regression test for the 2026-07 audit finding: 'reports.wishlist' was
 * used as a real admin.permission:<slug> middleware gate on a live route
 * but was never added to config/permission_groups.php — meaning
 * PermissionSeeder never created it, and a Super Admin had no way to
 * grant it to an Employee through the actual Roles & Permissions UI,
 * even though admin/super_admin were silently unaffected (they bypass
 * via User::hasAdminAccess()'s role check regardless of what's seeded).
 *
 * This walks every registered route's middleware, extracts every
 * admin.permission:<slug> gate actually in use, and asserts each one is
 * a real, seedable slug — so a future new report/feature type can't
 * silently repeat this exact gap.
 */
class PermissionSlugsAreRegisteredTest extends TestCase
{
    public function test_every_admin_permission_middleware_slug_is_registered_in_permission_groups(): void
    {
        $usedSlugs = collect(Route::getRoutes())
            ->flatMap(fn ($route) => $route->gatherMiddleware())
            ->filter(fn ($middleware) => str_starts_with($middleware, 'admin.permission:'))
            ->map(fn ($middleware) => substr($middleware, strlen('admin.permission:')))
            ->unique()
            ->values();

        $registeredSlugs = collect(config('permission_groups'))->flatten();

        $this->assertNotEmpty($usedSlugs, 'Expected at least one admin.permission:<slug> route to exist.');

        $missing = $usedSlugs->diff($registeredSlugs);

        $this->assertTrue(
            $missing->isEmpty(),
            'These permission slugs are used as route gates but missing from config/permission_groups.php '.
            '(PermissionSeeder will never create them, and no Super Admin can grant them): '.$missing->implode(', ')
        );
    }
}
