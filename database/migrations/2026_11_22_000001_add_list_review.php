<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PLAN M11: list review and duplicate detection.
 *
 * - pg_trgm and a trigram index on occupation_names.normalized_name (A8: trigram + aliases, no
 *   embeddings in Phase 1).
 * - publish_state `rejected` for a candidate the review turned down (D46), never indexable, and who
 *   reviewed an occupation's place on the list, when, with a note.
 * - duplicate_candidates: a possible duplicate pair, stored once (lower id first). A decided pair is
 *   never flagged again (D47).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE INDEX occupation_names_normalized_name_trgm_index ON occupation_names USING gin (normalized_name gin_trgm_ops)');

        Schema::table('occupations', function (Blueprint $table) {
            $table->foreignId('reviewed_by_id')->nullable()->after('ai_run_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('reviewed_at')->nullable()->after('reviewed_by_id');
            $table->text('review_note')->nullable()->after('reviewed_at');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE occupations
                DROP CONSTRAINT occupations_publish_state_check,
                ADD CONSTRAINT occupations_publish_state_check
                    CHECK (publish_state IN ('candidate', 'listed', 'published', 'merged', 'rejected')),
                ADD CONSTRAINT occupations_rejected_not_indexable_check
                    CHECK (publish_state <> 'rejected' OR NOT indexable),
                ADD CONSTRAINT occupations_reviewed_check
                    CHECK ((reviewed_by_id IS NULL) = (reviewed_at IS NULL))
            SQL);

        Schema::create('duplicate_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occupation_id')->constrained('occupations')->restrictOnDelete();
            $table->foreignId('other_occupation_id')->constrained('occupations')->restrictOnDelete();
            $table->decimal('similarity', 4, 3);
            $table->string('locale', 8);
            $table->text('name');
            $table->text('other_name');
            $table->string('status', 20)->default('open');
            $table->foreignId('decided_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestampsTz();

            $table->unique(['occupation_id', 'other_occupation_id']);
            $table->index('other_occupation_id');
            $table->index('status');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE duplicate_candidates
                ADD CONSTRAINT duplicate_candidates_order_check CHECK (occupation_id < other_occupation_id),
                ADD CONSTRAINT duplicate_candidates_similarity_check CHECK (similarity BETWEEN 0 AND 1),
                ADD CONSTRAINT duplicate_candidates_status_check
                    CHECK (status IN ('open', 'merged', 'kept_separate')),
                ADD CONSTRAINT duplicate_candidates_decided_check CHECK (
                    (status = 'open') = (decided_at IS NULL)
                    AND (decided_at IS NULL) = (decided_by_id IS NULL)
                )
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('duplicate_candidates');

        DB::statement(<<<'SQL'
            ALTER TABLE occupations
                DROP CONSTRAINT occupations_reviewed_check,
                DROP CONSTRAINT occupations_rejected_not_indexable_check,
                DROP CONSTRAINT occupations_publish_state_check,
                ADD CONSTRAINT occupations_publish_state_check
                    CHECK (publish_state IN ('candidate', 'listed', 'published', 'merged'))
            SQL);

        Schema::table('occupations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by_id');
            $table->dropColumn(['reviewed_at', 'review_note']);
        });

        DB::statement('DROP INDEX IF EXISTS occupation_names_normalized_name_trgm_index');
        DB::statement('DROP EXTENSION IF EXISTS pg_trgm');
    }
};
