<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('author_name')->nullable()->after('cover_image');
            // Free-text tag (e.g. "Styling Tips", "Fabric Care") rather than
            // a full relational taxonomy — the blog has no category browsing
            // feature to extend, so a lightweight string keeps this
            // proportionate to what's actually used today.
            $table->string('category')->nullable()->after('author_name');
            $table->string('meta_title_ar')->nullable()->after('category');
            $table->string('meta_title_en')->nullable()->after('meta_title_ar');
            $table->text('meta_description_ar')->nullable()->after('meta_title_en');
            $table->text('meta_description_en')->nullable()->after('meta_description_ar');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['author_name', 'category', 'meta_title_ar', 'meta_title_en', 'meta_description_ar', 'meta_description_en']);
        });
    }
};
