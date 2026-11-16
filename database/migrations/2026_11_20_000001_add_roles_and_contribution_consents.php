<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Roles and trust (M8): every person starts as a contributor. The trust counters count the person's
 * proposals that a reviewer accepted or rejected; a maintainer promotes people (atlas:user:promote).
 *
 * Contribution consent: signing up needs agreement that contributions are published under
 * CC BY-SA 4.0. Each agreement is kept with the terms version and the time, so a new version of the
 * terms can ask again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 16)->default('contributor');
            $table->unsignedInteger('accepted_proposals_count')->default(0);
            $table->unsignedInteger('rejected_proposals_count')->default(0);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE users ADD CONSTRAINT users_role_check
                CHECK (role IN ('contributor', 'trusted', 'reviewer', 'expert', 'maintainer'))
            SQL);

        Schema::create('contribution_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('terms_version', 32);
            $table->timestampTz('accepted_at');

            $table->unique(['user_id', 'terms_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribution_consents');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'accepted_proposals_count', 'rejected_proposals_count']);
        });
    }
};
