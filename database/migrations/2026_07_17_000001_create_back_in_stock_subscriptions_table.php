<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('back_in_stock_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Null = whole-product-level subscription (used when the product
            // has no meaningful size choice); set = tracking that one size.
            $table->foreignId('product_size_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            // Guards against duplicate signups for the same product+size+email.
            // Explicitly named — Laravel's auto-generated name for this
            // column combination (back_in_stock_subscriptions_product_id_
            // product_size_id_email_unique) is 70 characters, over MySQL's
            // 64-character identifier limit; SQLite has no such limit, which
            // is why this only fails against the real MySQL database, never
            // in the test suite (CACHE_STORE=array, DB_CONNECTION=sqlite).
            // Note: MySQL/SQLite both treat NULL as distinct-from-any-other-NULL
            // in a unique index, so this alone does NOT stop duplicate
            // whole-product (product_size_id = null) signups for the same
            // email — that case is additionally guarded at the application
            // layer in BackInStockSubscriptionController.
            $table->unique(['product_id', 'product_size_id', 'email'], 'back_in_stock_subs_product_size_email_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('back_in_stock_subscriptions');
    }
};
