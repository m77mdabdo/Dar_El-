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
        Schema::table('products', function (Blueprint $table) {
            // Was cascadeOnDelete() — deleting a category silently hard-deleted
            // every product in it (and, via further cascades, their images/
            // sizes/variants/reviews) with no guard. restrictOnDelete() makes
            // that impossible at the DB level; the application-level guard in
            // Admin\CategoryController::destroy() is the actual user-facing
            // block, this is the backstop.
            $table->dropForeign(['category_id']);
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
        });
    }
};
