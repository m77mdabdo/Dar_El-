<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            // Nullable/no-default additions only — the two existing rows
            // (Standard/Express Delivery, seeded earlier) keep working with
            // their current name_ar/name_en/fee/estimated_days untouched;
            // this just adds the extra structure the checkout page and
            // order snapshot now need.
            $table->string('code')->nullable()->unique()->after('id');
            $table->text('description_ar')->nullable()->after('name_en');
            $table->text('description_en')->nullable()->after('description_ar');
            $table->unsignedInteger('delivery_time_min_days')->nullable()->after('estimated_days');
            $table->unsignedInteger('delivery_time_max_days')->nullable()->after('delivery_time_min_days');
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });

        // Backfill code + min/max days for any pre-existing rows (parses
        // the old free-text "3-5" style estimated_days column) so nothing
        // that was already seeded is left with blank structured fields.
        foreach (DB::table('shipping_methods')->get() as $method) {
            $code = str($method->name_en)->contains('Express', true) ? 'express' : 'standard';

            [$min, $max] = $this->parseEstimatedDays($method->estimated_days);

            DB::table('shipping_methods')->where('id', $method->id)->update([
                'code' => $method->code ?? $code,
                'delivery_time_min_days' => $method->delivery_time_min_days ?? $min,
                'delivery_time_max_days' => $method->delivery_time_max_days ?? $max,
            ]);
        }

        Schema::table('orders', function (Blueprint $table) {
            // Snapshots of the shipping method actually selected at
            // checkout time — mirrors the existing order_items.product_name
            // pattern (a snapshot survives the parent row being edited or
            // deleted later; orders.shipping_method_id already exists and
            // stays as the live-reference FK for admin convenience).
            $table->string('shipping_method_code')->nullable()->after('shipping_method_id');
            $table->string('shipping_method_name')->nullable()->after('shipping_method_code');
            $table->unsignedInteger('shipping_delivery_min_days')->nullable()->after('shipping_method_name');
            $table->unsignedInteger('shipping_delivery_max_days')->nullable()->after('shipping_delivery_min_days');

            // Optional "Use My Current Location" capture — nullable, never
            // required, manual address entry is always sufficient on its own.
            $table->decimal('customer_latitude', 10, 7)->nullable()->after('address');
            $table->decimal('customer_longitude', 10, 7)->nullable()->after('customer_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_method_code', 'shipping_method_name',
                'shipping_delivery_min_days', 'shipping_delivery_max_days',
                'customer_latitude', 'customer_longitude',
            ]);
        });

        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn([
                'code', 'description_ar', 'description_en',
                'delivery_time_min_days', 'delivery_time_max_days', 'sort_order',
            ]);
        });
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected function parseEstimatedDays(?string $estimatedDays): array
    {
        if (! $estimatedDays || ! preg_match('/(\d+)\D+(\d+)/', $estimatedDays, $m)) {
            return [3, 5];
        }

        return [(int) $m[1], (int) $m[2]];
    }
};
