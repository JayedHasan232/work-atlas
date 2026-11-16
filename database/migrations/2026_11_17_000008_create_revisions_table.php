<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only history of every field value (rule 5). Each revision has exactly one author: a user or
 * an AI run. A revert is a new revision that points to the one it reverts. Rows are never updated or
 * deleted; see the trigger migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_value_id')->constrained()->restrictOnDelete();
            $table->jsonb('old_value')->nullable();
            $table->jsonb('new_value')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('ai_run_id')->nullable()->constrained()->restrictOnDelete();
            $table->text('comment')->nullable();
            $table->foreignId('reverts_revision_id')->nullable()->constrained('revisions')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['field_value_id', 'id']);
            $table->index('user_id');
            $table->index('ai_run_id');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE revisions
                ADD CONSTRAINT revisions_one_author_check CHECK (num_nonnulls(user_id, ai_run_id) = 1),
                ADD CONSTRAINT revisions_reverts_other_check CHECK (reverts_revision_id IS DISTINCT FROM id)
            SQL);

        Schema::table('field_values', function (Blueprint $table) {
            $table->foreign('current_revision_id')->references('id')->on('revisions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('field_values', function (Blueprint $table) {
            $table->dropForeign(['current_revision_id']);
        });

        Schema::dropIfExists('revisions');
    }
};
