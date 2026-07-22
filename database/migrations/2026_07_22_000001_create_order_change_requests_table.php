<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // change_cancel | exchange | return — see OrderChangeRequest::TYPES.
            $table->string('type');
            // Selected order_items.id values, or null when the request
            // concerns the whole order (single-item orders, or a customer
            // who didn't narrow it down) — a JSON array rather than a pivot
            // table, since this is an audit/contact record, not something
            // ever queried by individual item.
            $table->json('order_item_ids')->nullable();
            $table->string('reason');
            $table->text('notes')->nullable();
            // Only meaningful for type=exchange — free text (size or
            // product), not a hard reference to a real ProductSize/variant
            // row, since the admin resolves the actual swap manually over
            // WhatsApp rather than this driving any automated stock change.
            $table->string('desired_variant')->nullable();
            // pending | contacted | resolved — see OrderChangeRequest::STATUSES.
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_change_requests');
    }
};
