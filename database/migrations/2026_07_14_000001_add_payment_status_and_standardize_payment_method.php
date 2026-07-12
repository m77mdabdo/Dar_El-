<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status')->default(Order::PAYMENT_STATUS_PENDING)->after('payment_method');
        });

        // 'cod' was the historical value; standardizing to 'cash_on_delivery'
        // everywhere (view, JS, validation, model, admin, emails, invoice) —
        // this backfills every existing order so the whole dataset is
        // consistent, not just new orders going forward. The column's own
        // DB-level default is intentionally left as-is (changing it needs
        // doctrine/dbal, which this app doesn't otherwise need) — every
        // write path already sets payment_method explicitly, so the
        // column default is never actually relied on.
        DB::table('orders')->where('payment_method', 'cod')->update(['payment_method' => Order::PAYMENT_METHOD_COD]);

        // Existing orders predate payment_status entirely — infer a sane
        // value from their existing order status rather than leaving every
        // past order stamped "pending" regardless of what actually happened.
        DB::table('orders')->where('status', 'delivered')->update(['payment_status' => Order::PAYMENT_STATUS_PAID]);
        DB::table('orders')->where('status', 'cancelled')->update(['payment_status' => Order::PAYMENT_STATUS_CANCELLED]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });

        DB::table('orders')->where('payment_method', Order::PAYMENT_METHOD_COD)->update(['payment_method' => 'cod']);
    }
};
