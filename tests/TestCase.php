<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    /**
     * Spatie caches role/permission lookups (24h TTL by default,
     * config/permission.php) independently of the database. Combined with
     * RefreshDatabase wiping the DB between tests, a role/permission
     * created in one test can leave a stale cache entry that a later test
     * reads — an intermittent, test-order-dependent failure, not a real
     * bug. Clearing it before every test is Spatie's own documented fix.
     */
    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
