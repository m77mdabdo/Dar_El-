<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('status')->default('published')->after('is_active');
            $table->timestamp('scheduled_publish_at')->nullable()->after('status');
            $table->timestamp('published_at')->nullable()->after('scheduled_publish_at');
            $table->index('status');
        });

        // Backfill from the existing is_active boolean, which is kept as-is
        // (the storefront reads it directly) — status is a richer workflow
        // layered on top, derived from/to is_active via Product::applyStatus().
        DB::table('products')->where('is_active', true)->update(['status' => 'published', 'published_at' => now()]);
        DB::table('products')->where('is_active', false)->update(['status' => 'archived']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'scheduled_publish_at', 'published_at']);
        });
    }
};
