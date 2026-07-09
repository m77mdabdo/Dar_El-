<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Captured at checkout time so the invoice PDF and confirmation
            // email render in the customer's actual language later, even
            // though invoice generation runs in a queued job — a queue
            // worker process has no HTTP-request locale context of its own.
            $table->string('locale', 5)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
