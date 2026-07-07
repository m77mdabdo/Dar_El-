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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('coupon_code')->nullable()->after('status');
            $table->unsignedInteger('discount_amount')->default(0)->after('coupon_code');
            $table->foreignId('shipping_method_id')->nullable()->after('discount_amount')->constrained()->nullOnDelete();

            $table->index('coupon_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('shipping_method_id');
            $table->dropColumn(['coupon_code', 'discount_amount']);
        });
    }
};
