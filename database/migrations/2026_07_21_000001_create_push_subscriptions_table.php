<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            // Null = an anonymous browser subscription not yet tied to an
            // account — still usable for a back-in-stock alert (linked via
            // back_in_stock_subscriptions.push_subscription_id instead), just
            // not for order-status pushes, which are only ever sent by
            // querying this column (see PushNotificationService::sendToUser()).
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            // The push service endpoint URL is this subscription's real
            // identity (one per browser/device) — unique so re-subscribing
            // (e.g. permission re-granted after being cleared) updates the
            // same row instead of accumulating dead duplicates.
            $table->string('endpoint', 400)->unique();
            $table->string('p256dh');
            $table->string('auth');
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
