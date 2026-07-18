<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Deliberately one-directional: an admin curates "related products"
        // from product A's own edit form, and that's what shows on A's
        // page — linking a belt to an abaya doesn't automatically make the
        // abaya show up as "related" on the belt's own page too.
        Schema::create('product_related', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'related_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_related');
    }
};
