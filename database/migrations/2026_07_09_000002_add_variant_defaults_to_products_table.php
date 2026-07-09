<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku_prefix')->nullable()->after('meta_description_en');
            $table->unsignedInteger('default_stock')->nullable()->after('sku_prefix');
            $table->unsignedInteger('default_low_stock_threshold')->nullable()->after('default_stock');
            $table->decimal('weight', 8, 2)->nullable()->after('default_low_stock_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sku_prefix', 'default_stock', 'default_low_stock_threshold', 'weight']);
        });
    }
};
