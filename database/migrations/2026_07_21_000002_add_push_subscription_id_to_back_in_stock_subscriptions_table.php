<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('back_in_stock_subscriptions', function (Blueprint $table) {
            // Set only if this subscriber later opted into a push
            // notification for this specific signup (see
            // PushSubscriptionController::store()'s link_token handling) —
            // most rows will never have one, since push is an optional
            // addition to the email alert, never a replacement for it.
            $table->foreignId('push_subscription_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('back_in_stock_subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('push_subscription_id');
        });
    }
};
