<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Lets the customer-facing "invoice not ready" page (and any future
     * admin tooling) distinguish "still queued/processing, check back
     * soon" from "generation genuinely failed" — previously a failed
     * generation left no invoice row at all, so the page showed the same
     * "still preparing" message forever with no way to tell the two
     * apart. status reflects the outcome of the most recent attempt;
     * pdf_path/file-existence (unchanged, in Account\OrderController)
     * remains the actual authority on whether a download is servable, so
     * a failed *regeneration* attempt never hides a still-valid older PDF.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('pdf_path');
            $table->text('failed_reason')->nullable()->after('status');
            $table->timestamp('emailed_at')->nullable()->after('issued_at');
        });

        // Backfill: every pre-existing row already has a pdf_path from
        // the old generate-then-save flow, so it's safe to mark them all
        // as successfully generated rather than leaving them "pending".
        DB::table('invoices')->whereNotNull('pdf_path')->update(['status' => 'generated']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['status', 'failed_reason', 'emailed_at']);
        });
    }
};
