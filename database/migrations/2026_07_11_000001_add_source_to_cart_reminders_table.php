<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_reminders', function (Blueprint $table) {
            // Existing rows predate this distinction and were, in practice,
            // almost entirely sent by the scheduled command — 'automatic' is
            // the correct default backfill for them.
            $table->string('source')->default('automatic')->after('channel');
        });
    }

    public function down(): void
    {
        Schema::table('cart_reminders', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
