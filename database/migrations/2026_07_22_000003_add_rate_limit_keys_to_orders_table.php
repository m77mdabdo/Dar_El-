<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * customer_phone stores whatever raw text the customer typed — comparing
     * it directly across orders means "+201012345678" and "01012345678"
     * (the same real number) never match, which is exactly the gap that let
     * the phone-based abuse guard be bypassed by trivial formatting
     * variation. These two columns store the normalized/hashed comparison
     * keys computed at order-creation time (see CheckoutController::store()
     * and App\Support\PhoneNumberNormalizer / CheckoutAddressNormalizer), so
     * the rate-limit lookups can use a plain indexed equality match instead
     * of re-normalizing every historical row on every request.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_phone_normalized', 20)->nullable()->after('customer_phone')->index();
            $table->char('address_rate_limit_key', 64)->nullable()->after('address')->index();
        });

        // Backfill existing rows so the rate-limit window includes orders
        // placed just before this deploy, not just ones created after it.
        DB::table('orders')->orderBy('id')->chunkById(200, function ($orders) {
            foreach ($orders as $order) {
                DB::table('orders')->where('id', $order->id)->update([
                    'customer_phone_normalized' => \App\Support\PhoneNumberNormalizer::normalize((string) $order->customer_phone),
                    'address_rate_limit_key' => \App\Support\CheckoutAddressNormalizer::key(
                        (string) $order->governorate,
                        (string) $order->city,
                        (string) $order->address,
                    ),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['customer_phone_normalized', 'address_rate_limit_key']);
        });
    }
};
