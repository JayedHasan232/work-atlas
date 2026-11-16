<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One row per AI model call, failures included (written by AiPipeline\AiGateway from M9).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_runs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32);
            $table->string('model', 128);
            $table->string('prompt_key', 128);
            $table->unsignedSmallInteger('prompt_version');
            $table->char('input_hash', 64)->index();
            $table->text('raw_output')->nullable();
            $table->jsonb('parsed_output')->nullable();
            $table->string('status', 16)->default('pending')->index();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->decimal('cost_usd', 12, 6)->nullable();
            $table->string('batch_id', 128)->nullable()->index();
            $table->text('error')->nullable();
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->timestampsTz();

            $table->index(['prompt_key', 'prompt_version']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE ai_runs
                ADD CONSTRAINT ai_runs_status_check
                    CHECK (status IN ('pending', 'running', 'succeeded', 'failed')),
                ADD CONSTRAINT ai_runs_input_hash_check CHECK (input_hash ~ '^[0-9a-f]{64}$'),
                ADD CONSTRAINT ai_runs_prompt_version_check CHECK (prompt_version >= 1),
                ADD CONSTRAINT ai_runs_counts_check CHECK (
                    (input_tokens IS NULL OR input_tokens >= 0)
                    AND (output_tokens IS NULL OR output_tokens >= 0)
                    AND (cost_usd IS NULL OR cost_usd >= 0)
                ),
                ADD CONSTRAINT ai_runs_failed_has_error_check CHECK (status <> 'failed' OR error IS NOT NULL),
                ADD CONSTRAINT ai_runs_finished_after_started_check
                    CHECK (finished_at IS NULL OR started_at IS NULL OR finished_at >= started_at)
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_runs');
    }
};
