<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MySQL has no partial/conditional unique index (unlike Postgres'
     * `WHERE status = 'pending'`), so "at most one pending row per order"
     * can't be expressed as a unique index directly on (order_id, status).
     * The standard workaround: a column that mirrors order_id only while
     * status is pending, and is NULL otherwise (see OrderChangeRequest's
     * saving() hook) — a plain unique index on that column then only ever
     * collides between two *pending* rows for the same order, since both
     * MySQL and SQLite treat every NULL as distinct from every other NULL
     * under a unique index. This turns the previous check-then-create race
     * (two concurrent requests both see "no pending row" and both insert)
     * into something the database itself refuses, regardless of timing.
     */
    public function up(): void
    {
        Schema::table('order_change_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('pending_order_id')->nullable()->unique()->after('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_change_requests', function (Blueprint $table) {
            $table->dropUnique(['pending_order_id']);
            $table->dropColumn('pending_order_id');
        });
    }
};
