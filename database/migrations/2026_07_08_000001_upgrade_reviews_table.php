<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('title')->nullable()->after('rating');
            $table->string('status')->default('pending')->after('comment');
            $table->boolean('is_verified_purchase')->default(false)->after('status');
            $table->boolean('is_featured')->default(false)->after('is_verified_purchase');
            $table->unsignedInteger('helpful_count')->default(0)->after('is_featured');
            $table->timestamp('approved_at')->nullable()->after('helpful_count');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('rejected_by');
            $table->softDeletes()->after('updated_at');
        });

        // Backfill: is_approved boolean had no "rejected" concept, so an
        // un-actioned (false) review must become pending, not rejected —
        // otherwise real un-reviewed submissions would be misrepresented
        // as actively rejected.
        DB::table('reviews')->where('is_approved', true)->update(['status' => 'approved', 'approved_at' => now()]);
        DB::table('reviews')->where('is_approved', false)->update(['status' => 'pending']);

        Schema::table('reviews', function (Blueprint $table) {
            // Add the replacement indexes BEFORE dropping the old ones: MySQL
            // uses the (product_id, is_approved) composite index to satisfy the
            // product_id foreign key's indexing requirement, so dropping it
            // first (with nothing else covering product_id) fails with error
            // 1553. Creating (product_id, status) first gives the FK a new
            // covering index to fall back on.
            $table->index(['product_id', 'status']);
            $table->index('status');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'is_approved']);
            $table->dropIndex(['is_approved']);
            $table->dropColumn('is_approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false)->after('comment');
        });

        DB::table('reviews')->where('status', 'approved')->update(['is_approved' => true]);
        DB::table('reviews')->where('status', '!=', 'approved')->update(['is_approved' => false]);

        Schema::table('reviews', function (Blueprint $table) {
            // Same reasoning as up(): give product_id a covering index before
            // dropping (product_id, status)/status, or the FK drop fails.
            $table->index('is_approved');
            $table->index(['product_id', 'is_approved']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'status']);
            $table->dropIndex(['status']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn([
                'title', 'status', 'is_verified_purchase', 'is_featured', 'helpful_count',
                'approved_at', 'approved_by', 'rejected_at', 'rejected_by', 'rejection_reason',
                'deleted_at',
            ]);
        });
    }
};
