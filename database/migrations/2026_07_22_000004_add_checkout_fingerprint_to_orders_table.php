<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A hash of (normalized phone + cart contents) computed at submission
     * time — see CheckoutController::store(). Lets a double-click or a
     * network-retry resubmission of the exact same cart recognize "this
     * already went through" and reuse the existing order instead of
     * creating a second one (and decrementing stock twice) from one
     * checkout attempt. No backfill: existing orders have no submission to
     * deduplicate against, so a null fingerprint on old rows is correct,
     * not a gap.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->char('checkout_fingerprint', 64)->nullable()->after('address_rate_limit_key')->index();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('checkout_fingerprint');
        });
    }
};
