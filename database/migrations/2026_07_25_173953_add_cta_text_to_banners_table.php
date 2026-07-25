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
        // Only meaningful for hero-type banners (the CTA button on the
        // homepage hero) — offer/collection/category banners are entirely
        // clickable tiles with no separate button, so these stay null for
        // those types. Nullable so the hero falls back to a default phrase
        // when left blank, same as link_url already does.
        Schema::table('banners', function (Blueprint $table) {
            $table->string('cta_text_ar')->nullable()->after('link_url');
            $table->string('cta_text_en')->nullable()->after('link_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['cta_text_ar', 'cta_text_en']);
        });
    }
};
