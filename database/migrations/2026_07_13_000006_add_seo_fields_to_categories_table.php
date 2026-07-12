<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('meta_title_ar')->nullable()->after('image');
            $table->string('meta_title_en')->nullable()->after('meta_title_ar');
            $table->text('meta_description_ar')->nullable()->after('meta_title_en');
            $table->text('meta_description_en')->nullable()->after('meta_description_ar');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['meta_title_ar', 'meta_title_en', 'meta_description_ar', 'meta_description_en']);
        });
    }
};
