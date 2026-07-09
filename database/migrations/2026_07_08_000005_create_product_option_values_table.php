<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('hex_color', 7)->nullable();
            $table->string('swatch_image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_values');
    }
};
