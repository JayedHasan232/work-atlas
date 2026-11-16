<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Micro-task verification (PLAN M17).
 *
 * - `verification_tasks`: one yes/no question about one value, the current revision of a value slot.
 *   It stays open until three people agree with no dissent (supported) or someone disagrees
 *   (disputed); it goes stale when the value changes first. One task per revision.
 * - `verification_answers`: one answer per person per task (agree, disagree, or skip). Answers are
 *   private: only the outcome reaches the page.
 * - `users.confirmed_answers_count`: answers that agreed with a reached consensus, a trust counter
 *   next to the accepted proposals (A11c).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_value_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revision_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('occupation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('state', 16)->default('open');
            $table->integer('priority')->default(0);
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();

            $table->index(['state', 'priority', 'created_at']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE verification_tasks ADD CONSTRAINT verification_tasks_state_check
                CHECK (state IN ('open', 'supported', 'disputed', 'stale'))
            SQL);

        Schema::create('verification_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('answer', 16);
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['verification_task_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE verification_answers ADD CONSTRAINT verification_answers_answer_check
                CHECK (answer IN ('agree', 'disagree', 'skip'))
            SQL);

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('confirmed_answers_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('confirmed_answers_count');
        });

        Schema::dropIfExists('verification_answers');
        Schema::dropIfExists('verification_tasks');
    }
};
