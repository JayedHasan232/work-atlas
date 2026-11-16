<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What the write path (M5) needs to know about a revision, fixed when the row is written because
 * revisions and reviews are never updated:
 *
 * - `proposed`: recorded without becoming the current value; it waits for a review. A pending proposal
 *   is a proposed revision with no review yet.
 * - `confidence`: the AI's own confidence (0–1). Only AI revisions carry it.
 * - `replaces_revision_id`, `old_status`: the current revision and the status the value slot had when
 *   this revision became current. A revert restores both. For a direct revision they sit on the
 *   revision; a proposal becomes current only when accepted, so for it they sit on the accepting review.
 */
return new class extends Migration
{
    private const STATUSES = "'ai_draft', 'sourced', 'community_supported', 'verified', 'disputed'";

    public function up(): void
    {
        Schema::table('revisions', function (Blueprint $table) {
            $table->boolean('proposed')->default(false);
            $table->decimal('confidence', 4, 3)->nullable();
            $table->foreignId('replaces_revision_id')->nullable()->constrained('revisions')->restrictOnDelete();
            $table->string('old_status', 24)->nullable();
        });

        Schema::table('revision_reviews', function (Blueprint $table) {
            $table->foreignId('replaces_revision_id')->nullable()->constrained('revisions')->restrictOnDelete();
            $table->string('old_status', 24)->nullable();
        });

        DB::statement(sprintf(<<<'SQL'
            ALTER TABLE revisions
                ADD CONSTRAINT revisions_confidence_check
                    CHECK (confidence IS NULL OR (confidence BETWEEN 0 AND 1 AND ai_run_id IS NOT NULL)),
                ADD CONSTRAINT revisions_old_status_check
                    CHECK (old_status IS NULL OR old_status IN (%s)),
                ADD CONSTRAINT revisions_replaces_other_check
                    CHECK (replaces_revision_id IS DISTINCT FROM id),
                ADD CONSTRAINT revisions_proposal_not_applied_check
                    CHECK (NOT proposed OR (replaces_revision_id IS NULL AND old_status IS NULL))
            SQL, self::STATUSES));

        DB::statement(sprintf(<<<'SQL'
            ALTER TABLE revision_reviews
                ADD CONSTRAINT revision_reviews_old_status_check
                    CHECK (old_status IS NULL OR old_status IN (%s)),
                ADD CONSTRAINT revision_reviews_rejected_not_applied_check
                    CHECK (decision = 'accepted' OR (replaces_revision_id IS NULL AND old_status IS NULL))
            SQL, self::STATUSES));
    }

    public function down(): void
    {
        Schema::table('revision_reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replaces_revision_id');
            $table->dropColumn('old_status');
        });

        Schema::table('revisions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replaces_revision_id');
            $table->dropColumn(['proposed', 'confidence', 'old_status']);
        });
    }
};
