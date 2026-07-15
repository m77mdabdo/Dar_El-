<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // orders.created_at: filtered/sorted constantly (Order::latest(),
        // dashboard today/monthly revenue, 14-day chart range) with no
        // index today.
        Schema::table('orders', function (Blueprint $table) {
            $table->index('created_at');
        });

        // users.created_at: filtered by Admin\CustomerController (7-day/
        // date-range customer lists) and the dashboard's customer queries.
        Schema::table('users', function (Blueprint $table) {
            $table->index('created_at');
        });

        // carts.created_at/updated_at: filtered by Admin\CartController's
        // date-range filter and its 14-day charts (grouped by updated_at).
        Schema::table('carts', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['updated_at']);
        });
    }
};
