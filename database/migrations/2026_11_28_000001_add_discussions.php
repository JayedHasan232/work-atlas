<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Talk pages (PLAN M19).
 *
 * - `discussion_threads`: one topic on an occupation, optionally about one field (by key, because a
 *   field's definition gets new versions but keeps its key).
 * - `discussion_posts`: the first post and the replies. What a person wrote never changes and is never
 *   deleted; a moderator can only hide it (D70).
 * - `discussion_moderations`: every hide and show, append-only, so the history of a hidden post stays.
 *
 * Threads and posts can be updated only in their hidden state and counters; a trigger rejects any
 * other change and every delete. `migrate:rollback` still works because it drops the tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occupation_id')->constrained()->restrictOnDelete();
            $table->string('field_key', 64)->nullable();
            $table->string('title', 200);
            $table->string('locale', 8);
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('posts_count')->default(0);
            $table->timestampTz('last_posted_at')->nullable();
            $table->timestampTz('hidden_at')->nullable();
            $table->foreignId('hidden_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->index(['occupation_id', 'last_posted_at']);
        });

        Schema::create('discussion_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_thread_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->string('locale', 8);
            $table->jsonb('flags')->default('[]');
            $table->timestampTz('hidden_at')->nullable();
            $table->foreignId('hidden_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->index(['discussion_thread_id', 'id']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('discussion_moderations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_thread_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('discussion_post_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('action', 8);
            $table->text('reason')->nullable();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement("ALTER TABLE discussion_moderations ADD CONSTRAINT discussion_moderations_action_check CHECK (action IN ('hide', 'show'))");
        DB::statement('ALTER TABLE discussion_moderations ADD CONSTRAINT discussion_moderations_one_target_check CHECK ((discussion_thread_id IS NULL) <> (discussion_post_id IS NULL))');

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION atlas_guard_discussion_change() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION '% rows are never deleted; hide them instead', TG_TABLE_NAME
                        USING ERRCODE = 'restrict_violation';
                END IF;

                -- Only the hidden state and the thread's counters may change; every column a person wrote
                -- stays as it was. Comparing the rows without those columns covers both tables.
                IF (to_jsonb(NEW) - ARRAY['hidden_at', 'hidden_by', 'updated_at', 'posts_count', 'last_posted_at'])
                    IS DISTINCT FROM (to_jsonb(OLD) - ARRAY['hidden_at', 'hidden_by', 'updated_at', 'posts_count', 'last_posted_at'])
                THEN
                    RAISE EXCEPTION 'what a person wrote in % never changes; only the hidden state does', TG_TABLE_NAME
                        USING ERRCODE = 'restrict_violation';
                END IF;

                RETURN NEW;
            END;
            $$
            SQL);

        foreach (['discussion_threads', 'discussion_posts'] as $table) {
            DB::statement("CREATE TRIGGER {$table}_guard BEFORE UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION atlas_guard_discussion_change()");
            DB::statement("CREATE TRIGGER {$table}_no_truncate BEFORE TRUNCATE ON {$table} FOR EACH STATEMENT EXECUTE FUNCTION atlas_reject_history_change()");
        }

        DB::statement('CREATE TRIGGER discussion_moderations_append_only BEFORE UPDATE OR DELETE ON discussion_moderations FOR EACH ROW EXECUTE FUNCTION atlas_reject_history_change()');
        DB::statement('CREATE TRIGGER discussion_moderations_no_truncate BEFORE TRUNCATE ON discussion_moderations FOR EACH STATEMENT EXECUTE FUNCTION atlas_reject_history_change()');
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_moderations');
        Schema::dropIfExists('discussion_posts');
        Schema::dropIfExists('discussion_threads');
        DB::statement('DROP FUNCTION IF EXISTS atlas_guard_discussion_change()');
    }
};
