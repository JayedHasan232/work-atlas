<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contributions from readers and contributors (PLAN M16).
 *
 * - `error_reports`: a reader says something on an occupation page is wrong, optionally about one
 *   field. No login is needed and no IP address or contact detail is kept (rule 8); the signed-in
 *   person is kept when there is one. A maintainer or reviewer handles each report in the admin queue.
 * - `revisions.declared_interest`: what a person declared about a conflict of interest when they
 *   suggested the change (for example "I run a training centre"). Null when nothing was declared.
 * - `users.anonymous_credit`: the person's credit is shown without their name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occupation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('field_definition_id')->nullable()->constrained()->nullOnDelete();
            $table->string('locale', 8);
            $table->text('message');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('state', 16)->default('open');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('handled_at')->nullable();
            $table->timestampsTz();

            $table->index(['state', 'created_at']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE error_reports ADD CONSTRAINT error_reports_state_check
                CHECK (state IN ('open', 'resolved', 'dismissed', 'spam'))
            SQL);

        Schema::table('revisions', function (Blueprint $table) {
            $table->text('declared_interest')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('anonymous_credit')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('anonymous_credit');
        });

        Schema::table('revisions', function (Blueprint $table) {
            $table->dropColumn('declared_interest');
        });

        Schema::dropIfExists('error_reports');
    }
};
