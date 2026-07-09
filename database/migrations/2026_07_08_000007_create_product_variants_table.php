<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable();
            $table->unsignedInteger('price_override')->nullable();
            $table->unsignedInteger('sale_price')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->decimal('weight', 8, 2)->nullable();
            $table->unsignedInteger('low_stock_threshold')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('stock');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
