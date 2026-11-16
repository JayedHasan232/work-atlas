<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One value slot per occupation × field × locale × region. The value itself lives in `revisions`;
 * `current_revision_id` points to the accepted one (its foreign key is added with `revisions`).
 * A null locale means the value is the same in every language (a 1–5 scale); a null region means
 * all of Bangladesh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occupation_id')->constrained()->restrictOnDelete();
            $table->foreignId('field_definition_id')->constrained()->restrictOnDelete();
            $table->string('locale', 8)->nullable();
            $table->string('region', 32)->nullable();
            $table->unsignedBigInteger('current_revision_id')->nullable()->index();
            $table->string('status', 24)->default('ai_draft')->index();
            $table->smallInteger('review_priority')->default(0)->index();
            $table->timestampsTz();

            $table->index('field_definition_id');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE field_values
                ADD CONSTRAINT field_values_slot_unique
                    UNIQUE NULLS NOT DISTINCT (occupation_id, field_definition_id, locale, region),
                ADD CONSTRAINT field_values_status_check CHECK (
                    status IN ('ai_draft', 'sourced', 'community_supported', 'verified', 'disputed')
                )
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('field_values');
    }
};
