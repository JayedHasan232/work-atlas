<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moderation tools (PLAN M18).
 *
 * - `revision_patrols`: a reviewer looked at a person's edit in the patrol queue. One row per
 *   revision. A separate table, because revisions are append-only (D2).
 * - `revision_flags`: why an edit may be promotional (a declared interest, links, a domain or an
 *   organisation name the same person keeps adding). Written once when the edit is made (D67).
 * - `users.blocked_at`, `blocked_by`, `block_reason`: a maintainer blocked the person; they can still
 *   sign in and read, but not contribute (D69).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revision_patrols', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revision_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('revision_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revision_id')->constrained()->restrictOnDelete();
            $table->string('reason', 32);
            $table->text('detail')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['revision_id', 'reason']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE revision_flags ADD CONSTRAINT revision_flags_reason_check
                CHECK (reason IN ('interest', 'link', 'repeated_domain', 'organisation', 'repeated_organisation'))
            SQL);

        Schema::table('users', function (Blueprint $table) {
            $table->timestampTz('blocked_at')->nullable();
            $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('block_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('blocked_by');
            $table->dropColumn(['blocked_at', 'block_reason']);
        });

        Schema::dropIfExists('revision_flags');
        Schema::dropIfExists('revision_patrols');
    }
};
