<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Occupations sit in one ISCO-08 unit group. A merged occupation keeps its row (and slug, for the 301
 * redirect of D5) and points to the occupation it was merged into.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occupations', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('isco_code', 4)->index();
            $table->string('bsco_code', 8)->nullable()->index();
            $table->string('publish_state', 16)->default('candidate')->index();
            $table->boolean('indexable')->default(false);
            $table->foreignId('merged_into_id')->nullable()->constrained('occupations')->restrictOnDelete();
            $table->timestampsTz();

            $table->foreign('isco_code')->references('code')->on('isco_groups')->restrictOnDelete();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE occupations
                ADD CONSTRAINT occupations_slug_check CHECK (slug ~ '^[a-z0-9]+(-[a-z0-9]+)*$'),
                ADD CONSTRAINT occupations_publish_state_check
                    CHECK (publish_state IN ('candidate', 'listed', 'published', 'merged')),
                ADD CONSTRAINT occupations_merged_into_check CHECK (
                    (publish_state = 'merged') = (merged_into_id IS NOT NULL)
                    AND merged_into_id IS DISTINCT FROM id
                ),
                ADD CONSTRAINT occupations_merged_not_indexable_check
                    CHECK (publish_state <> 'merged' OR NOT indexable)
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('occupations');
    }
};
